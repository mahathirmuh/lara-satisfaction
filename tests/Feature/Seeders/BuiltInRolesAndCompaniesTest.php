<?php

namespace Tests\Feature\Seeders;

use App\Models\Organization\Company;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Database\Seeders\CompanySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Three roles (IT Admin > Super Admin > Employee) and two companies (MTI and MBM), on a new install and after the
 * upgrade of an existing one.
 */
class BuiltInRolesAndCompaniesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_install_has_three_roles_and_two_companies(): void
    {
        $this->seedAccessControl();
        $this->seed(CompanySeeder::class);

        $this->assertSame(['IT Admin', 'Super Admin', 'Employee'], Role::query()->orderBy('id')->pluck('name')->all());
        $this->assertSame(['MBM', 'MTI'], Company::query()->orderBy('code')->pluck('code')->all());
    }

    public function test_after_the_upgrade_the_managers_are_employees(): void
    {
        $this->seedAccessControl();
        $manager = Role::query()->create(['name' => 'Manager', 'job_titles' => ['*Manager']]);
        $financeManager = User::factory()->withRole($manager)->create(['job_title' => 'Finance Manager']);
        $superAdmin = $this->superAdmin();
        $superAdmin->roles()->attach($manager->id);

        (require database_path('migrations/2026_11_17_101833_remove_the_manager_role.php'))->up();

        $this->assertDatabaseMissing('roles', ['name' => 'Manager']);
        $this->assertSame(['Employee'], $financeManager->fresh()->roles->pluck('name')->all());
        $this->assertSame(['Super Admin'], $superAdmin->fresh()->roles->pluck('name')->all());
    }

    public function test_after_the_upgrade_only_mti_and_mbm_are_left(): void
    {
        $this->seed(CompanySeeder::class);
        $other = Company::factory()->create(['code' => 'SCM', 'name' => 'PT Sulawesi Cahaya Mineral']);
        $employee = User::factory()->create(['company_id' => $other->id]);

        (require database_path('migrations/2026_11_17_101632_keep_only_the_mti_and_mbm_companies.php'))->up();

        $this->assertSame(['MBM', 'MTI'], Company::query()->orderBy('code')->pluck('code')->all());
        $this->assertNull($employee->fresh()->company_id);
    }
}
