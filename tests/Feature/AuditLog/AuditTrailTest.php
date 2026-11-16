<?php

namespace Tests\Feature\AuditLog;

use App\Enums\PermissionAction;
use App\Models\Log\AuditLog;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\Role;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\EmployeeDirectorySync;
use App\Services\Auth\LdapAuthenticator;
use App\Services\Auth\LdapIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * What the audit trail records: sign-ins (and refused ones, never with a password), sign-outs, and every change made in
 * the administration, with only the values that moved. Survey answers are never part of it.
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
    }

    public function test_signing_in_and_out_with_the_network_account_is_recorded(): void
    {
        $employee = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->directory()->shouldReceive('authenticate')->andReturn($this->identity('budi.santoso@merdekabattery.com'));

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'network-password'])->assertRedirect();
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertSame([
            ['auth.login', $employee->id, 'LDAP'],
            ['auth.logout', $employee->id, null],
        ], AuditLog::query()->orderBy('id')->get()->map(fn (AuditLog $entry): array => [$entry->action, $entry->user_id, $entry->via])->all());
    }

    public function test_a_refused_sign_in_records_what_was_typed_and_why_never_the_password(): void
    {
        $this->employee(['email' => 'budi.santoso@merdekabattery.com', 'is_active' => false]);
        $directory = $this->directory();
        $directory->shouldReceive('authenticate')->with('budi.santoso', 'Secret-123')->andReturn($this->identity('budi.santoso@merdekabattery.com'));
        $directory->shouldReceive('authenticate')->with('nobody', 'Wrong-456')->andReturnNull();

        $this->post(route('login.store'), ['username' => 'budi.santoso', 'password' => 'Secret-123'])->assertSessionHasErrors('username');
        $this->post(route('login.store'), ['username' => 'nobody', 'password' => 'Wrong-456'])->assertSessionHasErrors('username');

        $refused = AuditLog::query()->where('action', AuditLogger::LOGIN_FAILED)->orderBy('id')->get();
        $this->assertSame([
            ['Account' => 'budi.santoso', 'Email' => 'budi.santoso@merdekabattery.com', 'Reason' => 'Account inactive'],
            ['Account' => 'nobody', 'Reason' => 'Wrong username or password, or the directory account is disabled'],
        ], $refused->pluck('new_values')->all());
        $this->assertSame([null, null], $refused->pluck('user_id')->all());
        $this->assertStringNotContainsString('Secret-123', AuditLog::query()->toBase()->pluck('new_values')->implode(''));
    }

    public function test_signing_in_with_microsoft_is_recorded_with_the_provider(): void
    {
        config(['services.azure.client_id' => 'azure-client-id', 'services.azure.client_secret' => 'azure-client-secret']);
        $employee = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        $this->fakeMicrosoftUser('budi.santoso@merdekabattery.com');

        $this->get(route('sso.callback', 'microsoft'))->assertRedirect();

        $entry = AuditLog::query()->sole();
        $this->assertSame([AuditLogger::LOGIN, $employee->id, 'Microsoft SSO'], [$entry->action, $entry->user_id, $entry->via]);
    }

    public function test_a_survey_is_recorded_as_it_is_made_changed_published_and_deleted(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->postJson(route('survey-management.survey.store'), [
            'title' => 'Operational Audit 2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'status' => 'draft',
            'estimated_minutes' => 20,
        ])->assertCreated();
        $survey = Survey::query()->where('title', 'Operational Audit 2027')->sole();

        $this->actingAs($admin)->putJson(route('survey-management.survey.update', $survey), [
            'title' => 'Operational Audit 2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-02-15',
            'status' => 'draft',
            'estimated_minutes' => 20,
        ])->assertOk();
        // Saved as it was: nothing to record.
        $this->actingAs($admin)->putJson(route('survey-management.survey.update', $survey), [
            'title' => 'Operational Audit 2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-02-15',
            'status' => 'draft',
            'estimated_minutes' => 20,
        ])->assertOk();
        $this->actingAs($admin)->deleteJson(route('survey-management.survey.destroy', $survey))->assertOk();

        $entries = AuditLog::query()->orderBy('id')->get();
        $this->assertSame(['survey.created', 'survey.updated', 'survey.deleted'], $entries->pluck('action')->all());
        $this->assertSame(['Title' => 'Operational Audit 2027', 'Start date' => '2027-01-01', 'End date' => '2027-01-31', 'Estimated minutes' => 20, 'Status' => 'Draft'], $entries[0]->new_values);
        $this->assertSame([['End date' => '2027-01-31'], ['End date' => '2027-02-15']], [$entries[1]->old_values, $entries[1]->new_values]);
        $this->assertSame('Operational Audit 2027', $entries[2]->old_values['Title']);
        $this->assertSame(['Survey', $survey->id, 'Operational Audit 2027', $admin->id, $admin->email], [$entries[2]->entity_type, $entries[2]->entity_id, $entries[2]->entity_label, $entries[2]->user_id, $entries[2]->user_email]);
    }

    public function test_publishing_a_survey_records_its_status(): void
    {
        [$survey] = $this->openSurveyFor([Department::factory()->create()], ['status' => 'draft']);

        $this->actingAs($this->superAdmin())->patchJson(route('survey-management.survey.status', $survey), ['status' => 'active'])->assertOk();

        $entry = AuditLog::query()->sole();
        $this->assertSame([AuditLogger::SURVEY_STATUS_CHANGED, ['Status' => 'Draft'], ['Status' => 'Active']], [$entry->action, $entry->old_values, $entry->new_values]);
    }

    public function test_deactivating_an_account_records_the_change(): void
    {
        $employee = $this->employee(['name' => 'Siti Rahma', 'email' => 'siti@merdekabattery.com', 'employee_id' => 'MBM-1001', 'company_id' => Company::factory()->create()->id]);

        $this->actingAs($this->superAdmin())->putJson(route('user-management.user-registered.update', $employee), [
            'name' => 'Siti Rahma',
            'email' => 'siti@merdekabattery.com',
            'employee_id' => 'MBM-1001',
            'company_id' => $employee->company_id,
            'is_active' => 0,
            'role_id' => [(int) config('merdeka.default_role_id')],
        ])->assertOk();

        $entry = AuditLog::query()->sole();
        $this->assertSame([AuditLogger::USER_UPDATED, ['Active' => true], ['Active' => false], 'siti@merdekabattery.com'], [$entry->action, $entry->old_values, $entry->new_values, $entry->entity_label]);
    }

    public function test_a_role_change_records_what_it_may_do_on_each_menu(): void
    {
        $role = Role::query()->create(['name' => 'Report Viewer', 'department_scope' => 'ALL']);
        $report = Menu::query()->where('route', 'survey-report.index')->sole();

        $this->actingAs($this->itAdmin())->putJson(route('user-management.role-permission.update', $role), [
            'name' => 'Report Viewer',
            'department_scope' => 'ALL',
            'menu_list' => [$report->id],
            'menu_permission' => [$report->id => [PermissionAction::Read->value]],
        ])->assertOk();

        $entry = AuditLog::query()->sole();
        $this->assertSame(AuditLogger::ROLE_UPDATED, $entry->action);
        $this->assertSame(['Menu Survey Report' => 'Read'], $entry->new_values);
        $this->assertSame(['Menu Survey Report' => null], $entry->old_values);
    }

    public function test_a_department_change_is_recorded_as_master_data(): void
    {
        $department = Department::factory()->create(['code' => 'FIN', 'name' => 'Finance', 'is_active' => true]);

        $this->actingAs($this->superAdmin())->putJson(route('master-data.department.update', $department), [
            'code' => 'FIN',
            'name' => 'Finance & Accounting',
            'description' => $department->description,
            'icon' => $department->icon,
            'color' => $department->color,
            'sort_order' => $department->sort_order,
            'is_active' => 1,
        ])->assertOk();

        $entry = AuditLog::query()->sole();
        $this->assertSame([AuditLogger::MASTER_UPDATED, 'Department', ['Name' => 'Finance'], ['Name' => 'Finance & Accounting']], [$entry->action, $entry->entity_type, $entry->old_values, $entry->new_values]);
    }

    public function test_survey_answers_are_never_recorded(): void
    {
        [$survey, $questions] = $this->openSurveyFor([Department::factory()->create()]);

        $this->actingAs($this->employee())
            ->postJson(route('survey-form.draft', $survey), ['answers' => [$questions['comment']->id => 'Something private']])
            ->assertOk();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_the_daily_import_is_recorded_as_the_system_when_it_changed_accounts(): void
    {
        $result = ['employees' => 174, 'created' => 2, 'updated' => 1, 'unchanged' => 171, 'deactivated' => 0, 'left' => 0, 'skipped_deleted' => 0];
        $this->mock(EmployeeDirectorySync::class, fn (MockInterface $mock) => $mock->shouldReceive('sync')->andReturn(
            $result,
            [...$result, 'created' => 0, 'updated' => 0, 'unchanged' => 174],
        ));

        $this->artisan('users:sync-directory', ['--without-photos' => true])->assertSuccessful();
        $this->artisan('users:sync-directory', ['--without-photos' => true])->assertSuccessful();

        $entry = AuditLog::query()->sole();
        $this->assertSame(AuditLogger::USERS_SYNCED, $entry->action);
        $this->assertNull($entry->user_id);
        $this->assertSame(['Employees' => 174, 'Created' => 2, 'Updated' => 1, 'Deactivated' => 0, 'No longer in an assessed department' => 0], $entry->new_values);
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
    protected function identity(string $email): LdapIdentity
    {
        return new LdapIdentity(
            username: 'budi.santoso',
            distinguishedName: 'CN=Budi Santoso,OU=Staff,DC=example,DC=com',
            email: $email,
            name: 'Budi Santoso',
        );
    }

    /**
     * Make the Microsoft provider return the given account.
     */
    protected function fakeMicrosoftUser(string $email): void
    {
        $socialUser = (new SocialiteUser)->setRaw(['mail' => $email])->map(['id' => 'azure-object-id', 'name' => 'Budi Santoso', 'email' => $email]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('azure')->andReturn($provider);
    }
}
