<?php

namespace Tests\Feature\Auth;

use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\UserManagement\User;
use App\Services\Auth\DirectoryPerson;
use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\EmployeeDirectorySync;
use App\Services\Auth\LdapAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * The employees of the assessed departments are imported from the directory
 * (users:sync-directory, daily, and from User Management) before they sign in.
 */
class EmployeeDirectorySyncTest extends TestCase
{
    use RefreshDatabase;

    protected Department $supplyChain;

    protected Department $safety;

    protected Company $mti;

    protected Company $mbm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
        config(['merdeka.sso.allowed_domains' => ['merdekabattery.com']]);

        $this->supplyChain = Department::factory()->create([
            'code' => 'SCM', 'name' => 'Supply Chain', 'directory_names' => ['Supply Chain Management'],
            'manager_job_titles' => ['Supply Chain*Manager'],
        ]);
        $this->safety = Department::factory()->create(['code' => 'OHS', 'name' => 'Occupational Health & Safety', 'directory_names' => null]);
        $this->mti = Company::factory()->create(['code' => 'MTI', 'name' => 'PT Merdeka Tsingshan Indonesia']);
        $this->mbm = Company::factory()->create(['code' => 'MBM', 'name' => 'PT Merdeka Battery Materials']);
    }

    public function test_the_employees_of_the_assessed_departments_are_imported(): void
    {
        $this->directoryHas([
            $this->person('budi@merdekabattery.com', 'Supply Chain Management', ['name' => 'Budi Santoso', 'jobTitle' => 'Supply Chain Manager', 'employeeId' => 'MTI240001', 'company' => 'PT. Merdeka Tsingshan Indonesia']),
            $this->person('citra@merdekabattery.com', 'occupational health and safety', ['company' => 'MBMA']),
            $this->person('dedi@merdekabattery.com', 'Supply Chain Management', ['company' => null]),
            $this->person('eko@merdekabattery.com', 'Maintenance'),
            $this->person('fajar@merdekabattery.com', 'Supply Chain Management', ['isEnabled' => false]),
            $this->person('gita@merdekabattery.com', 'Supply Chain Management', ['employeeId' => null]),
            $this->person('hana@gmail.com', 'Supply Chain Management'),
        ]);

        $result = app(EmployeeDirectorySync::class)->sync();

        $this->assertSame(['employees' => 3, 'created' => 3, 'updated' => 0, 'unchanged' => 0, 'deactivated' => 0, 'left' => 0, 'skipped_deleted' => 0], $result);
        $this->assertEqualsCanonicalizing(['budi@merdekabattery.com', 'citra@merdekabattery.com', 'dedi@merdekabattery.com'], User::query()->pluck('email')->all());

        $budi = User::query()->where('email', 'budi@merdekabattery.com')->sole();
        $this->assertSame(['Budi Santoso', 'MTI240001', 'Supply Chain Manager', $this->supplyChain->id, $this->mti->id, true], [
            $budi->name, $budi->employee_id, $budi->job_title, $budi->department_id, $budi->company_id, $budi->is_active,
        ]);
        $this->assertSame(['Employee'], $budi->roles->pluck('name')->all());
        $this->assertNotNull($budi->directory_synced_at);

        $citra = User::query()->where('email', 'citra@merdekabattery.com')->sole();
        $this->assertSame([$this->safety->id, $this->mbm->id], [$citra->department_id, $citra->company_id]);
        $this->assertSame(['Employee'], $citra->roles->pluck('name')->all());
        $this->assertSame($this->mti->id, User::query()->where('email', 'dedi@merdekabattery.com')->value('company_id'));
        $this->assertNotNull(EmployeeDirectorySync::lastRun());
    }

    public function test_known_accounts_are_refreshed_but_keep_what_administrators_set(): void
    {
        $itAdmin = $this->itAdmin(['email' => 'budi@merdekabattery.com', 'name' => 'Budi', 'job_title' => null]);
        $onLeave = $this->employee(['email' => 'citra@merdekabattery.com', 'is_active' => false]);
        $this->directoryHas([
            $this->person('budi@merdekabattery.com', 'Supply Chain Management', ['name' => 'Budi Santoso', 'jobTitle' => 'Supply Chain Officer']),
            $this->person('citra@merdekabattery.com', 'Supply Chain Management'),
        ]);

        $result = app(EmployeeDirectorySync::class)->sync();

        $this->assertSame([2, 0, 2], [$result['employees'], $result['created'], $result['updated']]);
        $itAdmin->refresh();
        $this->assertSame(['Budi Santoso', 'Supply Chain Officer', $this->supplyChain->id], [$itAdmin->name, $itAdmin->job_title, $itAdmin->department_id]);
        $this->assertTrue($itAdmin->isItAdmin());
        $this->assertFalse($onLeave->fresh()->is_active);

        $again = app(EmployeeDirectorySync::class)->sync();
        $this->assertSame([0, 2], [$again['updated'], $again['unchanged']]);
    }

    public function test_directory_changes_reach_known_accounts(): void
    {
        $disabled = $this->departmentEmployee(['email' => 'budi@merdekabattery.com']);
        $moved = $this->departmentEmployee(['email' => 'citra@merdekabattery.com']);
        $gone = $this->departmentEmployee(['email' => 'dedi@merdekabattery.com', 'directory_synced_at' => now()->subDay()]);
        $deleted = $this->employee(['email' => 'eko@merdekabattery.com']);
        $deleted->delete();
        $this->directoryHas([
            $this->person('budi@merdekabattery.com', 'Supply Chain Management', ['isEnabled' => false]),
            $this->person('citra@merdekabattery.com', 'Maintenance'),
            $this->person('eko@merdekabattery.com', 'Supply Chain Management'),
        ]);

        $result = app(EmployeeDirectorySync::class)->sync();

        $this->assertSame([0, 1, 2, 1], [$result['employees'], $result['deactivated'], $result['left'], $result['skipped_deleted']]);
        $this->assertFalse($disabled->fresh()->is_active);
        $this->assertNull($moved->fresh()->department_id);
        $this->assertTrue($moved->fresh()->is_active);
        $this->assertNull($gone->fresh()->department_id);
        $this->assertSoftDeleted($deleted);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $this->directoryHas([$this->person('budi@merdekabattery.com', 'Supply Chain Management')]);

        $this->artisan('users:sync-directory', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run, nothing saved. 1 employees in the assessed departments: 1 created')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
        $this->assertNull(EmployeeDirectorySync::lastRun());
    }

    public function test_the_daily_command_imports_the_employees(): void
    {
        $this->directoryHas([$this->person('budi@merdekabattery.com', 'Supply Chain Management')]);

        $this->artisan('users:sync-directory')
            ->expectsOutputToContain('1 employees in the assessed departments: 1 created, 0 updated, 0 unchanged.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'budi@merdekabattery.com', 'department_id' => $this->supplyChain->id]);
    }

    public function test_an_empty_or_unreachable_directory_changes_nothing(): void
    {
        $employee = $this->departmentEmployee(['directory_synced_at' => now()]);
        $this->directoryHas([]);

        $this->artisan('users:sync-directory')->assertFailed();

        $this->assertNotNull($employee->fresh()->department_id);
    }

    public function test_administrators_can_import_from_user_management(): void
    {
        $this->directoryHas([$this->person('budi@merdekabattery.com', 'Supply Chain Management')]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.sync'))
            ->assertOk()
            ->assertJsonPath('message', '1 employees imported from the directory: 1 new, 0 updated, 0 deactivated, 0 no longer in an assessed department.');

        $this->actingAs($this->superAdmin())
            ->get(route('user-management.user-registered.index'))
            ->assertOk()
            ->assertSee('Sync from Directory')
            ->assertSee('(1 employees)');

        $this->actingAs($this->departmentEmployee())
            ->postJson(route('user-management.user-registered.sync'))
            ->assertForbidden();
    }

    public function test_an_unreachable_directory_is_reported_in_user_management(): void
    {
        $this->mock(LdapAuthenticator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('people')->andThrow(new DirectoryUnavailableException('The directory search failed.'));
        });

        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.sync'))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'The directory cannot be reached right now. Please try again shortly.');
    }

    /**
     * Replace the network directory with one listing these people.
     *
     * @param  array<int, DirectoryPerson>  $people
     */
    protected function directoryHas(array $people): void
    {
        $this->mock(LdapAuthenticator::class, function (MockInterface $mock) use ($people): void {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('people')->andReturn($people);
        });
    }

    /**
     * A directory account; enabled with an employee ID unless overridden.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function person(string $email, ?string $department, array $overrides = []): DirectoryPerson
    {
        return new DirectoryPerson(...[
            'username' => strtok($email, '@'),
            'email' => $email,
            'name' => ucfirst((string) strtok($email, '@')),
            'jobTitle' => 'Officer',
            'employeeId' => sprintf('MTI%06d', crc32($email) % 1000000),
            'department' => $department,
            'company' => 'PT. Merdeka Tsingshan Indonesia',
            'isEnabled' => true,
            ...$overrides,
        ]);
    }
}
