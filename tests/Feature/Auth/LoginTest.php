<?php

namespace Tests\Feature\Auth;

use App\Enums\PermissionAction;
use App\Helpers\LogApps;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Log\LogActivity;
use App\Models\Organization\Department;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\LdapAuthenticator;
use App\Services\Auth\LdapIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_address_of_the_application_opens_the_sign_in_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_signed_in_people_go_on_to_their_home_page(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)->get('/')->assertRedirect(route('login'));
        $this->actingAs($employee)->get(route('login'))->assertRedirect($employee->homeUrl());
    }

    public function test_the_landing_page_and_its_menu_are_gone_after_the_upgrade(): void
    {
        $this->seedAccessControl();
        $masterData = Menu::query()->where('title', 'Master Data')->value('id');
        $landing = Menu::query()->create(['title' => 'Landing Page', 'route' => 'master-data.landing-content.index', 'parent_id' => $masterData, 'sort_order' => 3]);
        $superAdmin = Role::query()->where('name', 'Super Admin')->firstOrFail();
        $superAdmin->menus()->attach($landing->id);
        MenuRolePermission::query()->create(['menu_id' => $landing->id, 'role_id' => $superAdmin->id, 'permission_id' => PermissionAction::Read->value]);

        (require database_path('migrations/2026_11_17_093604_remove_the_landing_page.php'))->up();

        $this->assertDatabaseMissing('menu', ['route' => 'master-data.landing-content.index']);
        $this->assertDatabaseMissing('menu_role_permission', ['menu_id' => $landing->id]);
        $this->assertFalse(Schema::hasTable('landing_contents'));
    }

    public function test_login_page_offers_sso_and_the_network_account_only(): void
    {
        config(['services.azure.client_id' => null, 'services.azure.client_secret' => null]);
        $this->directory();

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeInOrder(['Sign In with SSO', route('sso.redirect', 'microsoft'), 'Coming soon', 'Sign in with Network Account (LDAP)', 'name="username"', 'name="password"'], false)
            ->assertDontSee('Email or Employee ID')
            ->assertDontSee(route('sso.redirect', 'google'));
    }

    public function test_network_sign_in_is_shown_as_coming_soon_until_ldap_is_configured(): void
    {
        $this->configureSso();
        config(['ldap.url' => null, 'ldap.bind_dn' => null]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeInOrder(['Sign in with Network Account (LDAP)', 'Coming soon', '<fieldset disabled>'], false);
    }

    public function test_configured_sign_in_methods_are_offered_without_the_coming_soon_label(): void
    {
        $this->configureSso();
        config(['ldap.url' => 'ldaps://dc.example.com:636', 'ldap.bind_dn' => 'CN=Reader,DC=example,DC=com']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('sso.redirect', 'microsoft'))
            ->assertSee(route('sso.redirect', 'google'))
            ->assertDontSee('Coming soon')
            ->assertDontSee('<fieldset disabled>', false);
    }

    public function test_employee_signs_in_with_the_network_account_and_lands_on_my_survey(): void
    {
        $employee = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->once()
            ->with('budi.santoso', 'network-password')
            ->andReturn($this->identity('budi.santoso@merdekabattery.com'));

        $this->post(route('login.store'), [
            'username' => ' budi.santoso@merdekabattery.com ',
            'password' => 'network-password',
        ])->assertRedirect(route('survey-form.index'));

        $this->assertAuthenticatedAs($employee);
        $this->assertNotNull($employee->fresh()->last_login_at);
    }

    /**
     * Job title from the directory => roles it gives.
     *
     * @return array<string, array{0: string|null, 1: array<int, string>}>
     */
    public static function jobTitleRoles(): array
    {
        return [
            'owner of the survey' => ['Support Services Deputy General Manager', ['Super Admin', 'Manager']],
            'department manager' => ['Finance Manager', ['Manager']],
            'deputy general manager' => ['Deputy GM', ['Manager']],
            'assistant of the general manager' => ['Assistant of General Manager', ['Employee']],
            'officer' => ['IT Support Officer', ['Employee']],
            'no job title' => [null, ['Employee']],
        ];
    }

    /**
     * @param  array<int, string>  $roles
     */
    #[DataProvider('jobTitleRoles')]
    public function test_job_title_from_the_directory_decides_the_roles(?string $jobTitle, array $roles): void
    {
        $user = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->once()
            ->andReturn($this->identity('budi.santoso@merdekabattery.com', jobTitle: $jobTitle));

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame($jobTitle, $user->job_title);
        $this->assertSame($roles, $user->roles->sortBy('id')->pluck('name')->values()->all());
    }

    /**
     * Employee ID entered by hand, employee ID from the directory => the one kept.
     *
     * @return array<string, array{0: string|null, 1: string|null, 2: string|null}>
     */
    public static function directoryEmployeeIds(): array
    {
        return [
            'recorded from the directory' => [null, ' MTI240001 ', 'MTI240001'],
            'directory replaces the one entered by hand' => ['MBM-1001', 'MTI240001', 'MTI240001'],
            'empty in the directory keeps the one entered by hand' => ['MBM-1001', null, 'MBM-1001'],
            'invalid in the directory is not recorded' => ['MBM-1001', 'MTI 240001', 'MBM-1001'],
        ];
    }

    #[DataProvider('directoryEmployeeIds')]
    public function test_employee_id_from_the_directory_is_recorded_at_sign_in(?string $known, ?string $fromDirectory, ?string $kept): void
    {
        $user = $this->employee(['email' => 'budi.santoso@merdekabattery.com', 'employee_id' => $known]);
        $this->directory()->shouldReceive('authenticate')->once()
            ->andReturn($this->identity('budi.santoso@merdekabattery.com', employeeId: $fromDirectory));

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertRedirect(route('survey-form.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($kept, $user->fresh()->employee_id);
    }

    public function test_the_directory_department_decides_whether_the_employee_is_a_respondent(): void
    {
        $finance = Department::factory()->create(['name' => 'Finance']);
        $user = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->twice()->andReturn(
            $this->identity('budi.santoso@merdekabattery.com', department: 'Finance'),
            $this->identity('budi.santoso@merdekabattery.com', department: 'Maintenance'),
        );

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password']);
        $this->assertSame($finance->id, $user->fresh()->department_id);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password']);
        $this->assertNull($user->fresh()->department_id);
    }

    public function test_employee_id_of_another_account_is_not_taken_over_at_sign_in(): void
    {
        $holder = $this->employee(['employee_id' => 'MTI240001']);
        $user = $this->employee(['email' => 'budi.santoso@merdekabattery.com', 'employee_id' => 'MBM-1001']);
        $this->directory()->shouldReceive('authenticate')->once()
            ->andReturn($this->identity('budi.santoso@merdekabattery.com', employeeId: 'MTI240001'));

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertRedirect(route('survey-form.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('MBM-1001', $user->fresh()->employee_id);
        $this->assertSame('MTI240001', $holder->fresh()->employee_id);
    }

    public function test_employee_id_held_by_a_deleted_account_is_freed_at_sign_in(): void
    {
        $deleted = $this->employee(['employee_id' => 'MTI240001']);
        $deleted->delete();
        $user = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->once()
            ->andReturn($this->identity('budi.santoso@merdekabattery.com', employeeId: 'MTI240001'));

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertRedirect(route('survey-form.index'));

        $this->assertSame('MTI240001', $user->fresh()->employee_id);
        $this->assertNull($deleted->fresh()->employee_id);
    }

    public function test_a_changed_job_title_changes_the_roles_but_keeps_the_it_admin_role(): void
    {
        $manager = $this->manager(['email' => 'budi.santoso@merdekabattery.com', 'job_title' => 'Finance Manager']);
        $itAdmin = $this->itAdmin(['email' => 'mahathir@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->twice()->andReturn(
            $this->identity('budi.santoso@merdekabattery.com', jobTitle: 'Finance Officer'),
            $this->identity('mahathir@merdekabattery.com', jobTitle: 'IT Support Officer'),
        );

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password']);
        $this->post(route('logout'));
        $this->post(route('login.store'), ['username' => 'mahathir', 'password' => 'network-password']);

        $this->assertSame(['Employee'], $manager->fresh()->roles->pluck('name')->all());
        $this->assertSame(['IT Admin', 'Employee'], $itAdmin->fresh()->roles->sortBy('id')->pluck('name')->values()->all());
    }

    public function test_wrong_network_password_gets_the_generic_message(): void
    {
        $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->once()->andReturnNull();

        $this->from(route('login'))
            ->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_requires_both_fields(): void
    {
        $this->directory()->shouldNotReceive('authenticate');

        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['username', 'password']);

        $this->assertGuest();
    }

    public function test_verified_person_without_an_account_is_rejected_when_auto_registration_is_disabled(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => false]);
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity('new.hire@merdekabattery.com'));

        $this->post(route('login.store'), ['username' => 'new.hire', 'password' => 'network-password'])
            ->assertSessionHasErrors(['username' => 'Your network account is not registered in this application. Please contact HR or the IT Helpdesk.']);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'new.hire@merdekabattery.com']);
    }

    public function test_verified_person_of_an_allowed_domain_is_registered_as_employee_when_enabled(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => true]);
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity('new.hire@merdekabattery.com', 'New Hire'));

        $this->post(route('login.store'), ['username' => 'new.hire', 'password' => 'network-password'])
            ->assertRedirect(route('survey-form.index'));

        $user = User::query()->where('email', 'new.hire@merdekabattery.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('New Hire', $user->name);
        $this->assertSame(['Employee'], $user->roles->pluck('name')->all());
    }

    public function test_deleted_account_is_not_registered_again(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => true]);
        $deleted = User::factory()->create(['email' => 'left@merdekabattery.com']);
        $deleted->delete();
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity('left@merdekabattery.com'));

        $this->post(route('login.store'), ['username' => 'left', 'password' => 'network-password'])
            ->assertSessionHasErrors(['username' => 'Your network account is not registered in this application. Please contact HR or the IT Helpdesk.']);

        $this->assertGuest();
        $this->assertSoftDeleted($deleted);
        $this->assertSame(1, User::withTrashed()->where('email', 'left@merdekabattery.com')->count());
    }

    public function test_network_account_without_an_email_cannot_be_matched_to_an_account(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => true]);
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity(null));

        $this->post(route('login.store'), ['username' => 'operator01', 'password' => 'network-password'])
            ->assertSessionHasErrors(['username' => 'Your network account is not registered in this application. Please contact HR or the IT Helpdesk.']);

        $this->assertGuest();
    }

    public function test_inactive_account_is_not_signed_in(): void
    {
        $this->employee(['email' => 'left@merdekabattery.com', 'is_active' => false]);
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity('left@merdekabattery.com'));

        $this->post(route('login.store'), ['username' => 'left', 'password' => 'network-password'])
            ->assertSessionHasErrors(['username' => 'Your account is inactive. Please contact the IT Helpdesk.']);

        $this->assertGuest();
    }

    public function test_directory_outage_is_reported_and_not_counted_as_a_failed_attempt(): void
    {
        Exceptions::fake();
        $this->directory()->shouldReceive('authenticate')->once()
            ->andThrow(new DirectoryUnavailableException('The directory could not be reached.'));

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertSessionHasErrors(['username' => 'The network sign-in service cannot be reached right now. Please try again shortly.']);

        $this->assertGuest();
        Exceptions::assertReported(DirectoryUnavailableException::class);
        $this->assertSame(0, RateLimiter::attempts('budi.santoso|127.0.0.1'));
    }

    public function test_network_sign_in_is_refused_until_ldap_is_configured(): void
    {
        config(['ldap.url' => null, 'ldap.bind_dn' => null]);

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertSessionHasErrors(['username' => 'Network sign-in is not configured yet. Please sign in with Microsoft or contact the IT Helpdesk.']);

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_too_many_failed_attempts(): void
    {
        $this->directory()->shouldReceive('authenticate')->times(LoginRequest::MAX_ATTEMPTS)->andReturnNull();

        foreach (range(1, LoginRequest::MAX_ATTEMPTS) as $attempt) {
            $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('Too many login attempts', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_user_can_sign_out(): void
    {
        $this->actingAs($this->employee())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_signed_in_user_visiting_the_login_page_is_sent_home(): void
    {
        $this->actingAs($this->employee())
            ->get(route('login'))
            ->assertRedirect(route('survey-form.index'));
    }

    public function test_password_is_redacted_in_the_activity_log(): void
    {
        $this->directory()->shouldReceive('authenticate')->once()->andReturnNull();

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'Secret-Value-123']);

        $log = LogActivity::query()->where('route_name', 'login.store')->sole();

        $this->assertStringNotContainsString('Secret-Value-123', $log->data);
        $this->assertSame(LogApps::REDACTED, json_decode($log->data, true)['password']);
    }

    /**
     * Replace the network directory with a configured double.
     */
    protected function directory(): MockInterface
    {
        return $this->mock(LdapAuthenticator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
        });
    }

    /**
     * A person verified by the directory.
     */
    protected function identity(?string $email, ?string $name = 'Budi Santoso', ?string $jobTitle = null, ?string $employeeId = null, ?string $department = null): LdapIdentity
    {
        return new LdapIdentity(
            username: 'budi.santoso',
            distinguishedName: 'CN=Budi Santoso,OU=Staff,DC=example,DC=com',
            email: $email,
            name: $name,
            jobTitle: $jobTitle,
            employeeId: $employeeId,
            department: $department,
        );
    }

    /**
     * Microsoft and Google app registrations.
     */
    protected function configureSso(): void
    {
        config([
            'services.azure.client_id' => 'azure-client-id',
            'services.azure.client_secret' => 'azure-client-secret',
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
        ]);
    }
}
