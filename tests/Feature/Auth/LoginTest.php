<?php

namespace Tests\Feature\Auth;

use App\Helpers\LogApps;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Log\LogActivity;
use App\Models\UserManagement\User;
use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\LdapAuthenticator;
use App\Services\Auth\LdapIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

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
    protected function identity(?string $email, ?string $name = 'Budi Santoso'): LdapIdentity
    {
        return new LdapIdentity(
            username: 'budi.santoso',
            distinguishedName: 'CN=Budi Santoso,OU=Staff,DC=example,DC=com',
            email: $email,
            name: $name,
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
