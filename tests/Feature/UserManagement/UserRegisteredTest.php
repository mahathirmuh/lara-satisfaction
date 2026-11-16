<?php

namespace Tests\Feature\UserManagement;

use App\Models\Organization\Company;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegisteredTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
        $this->company = Company::factory()->create();
    }

    public function test_admin_registers_an_employee_without_a_usable_password(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.store'), $this->payload())
            ->assertCreated();

        $user = User::query()->where('email', 'siti@merdekabattery.com')->sole();
        $this->assertSame('MBM-1001', $user->employee_id);
        $this->assertSame(['Employee'], $user->roles->pluck('name')->all());
        $this->assertNotEmpty($user->password);
        $this->assertFalse(Hash::check('', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_a_submitted_password_is_ignored_as_users_only_sign_in_with_sso_or_ldap(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.store'), $this->payload(['password' => 'Merdeka2026']))
            ->assertCreated();

        $user = User::query()->where('email', 'siti@merdekabattery.com')->sole();
        $this->assertFalse(Hash::check('Merdeka2026', $user->password));

        $this->actingAs($this->superAdmin())
            ->putJson(route('user-management.user-registered.update', $user), $this->payload(['password' => 'Merdeka2027']))
            ->assertOk();

        $this->assertFalse(Hash::check('Merdeka2027', $user->fresh()->password));
    }

    public function test_roles_given_by_job_title_follow_the_job_title_while_other_roles_are_kept(): void
    {
        $auditor = Role::factory()->create(['name' => 'Auditor']);
        $employeeRoleId = (int) config('merdeka.default_role_id');

        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.store'), $this->payload([
                'job_title' => '  OHS   Manager ',
                'role_id' => [$employeeRoleId, $auditor->id],
            ]))
            ->assertCreated();

        $user = User::query()->where('email', 'siti@merdekabattery.com')->sole();
        $this->assertSame('OHS Manager', $user->job_title);
        $this->assertSame(['Manager', 'Auditor'], $user->roles->sortBy('id')->pluck('name')->values()->all());

        $this->actingAs($this->superAdmin())
            ->putJson(route('user-management.user-registered.update', $user), $this->payload([
                'job_title' => 'Safety Officer',
                'role_id' => [],
            ]))
            ->assertOk();

        $this->assertSame(['Employee'], $user->fresh()->roles->pluck('name')->all());
    }

    public function test_registering_the_email_of_a_deleted_account_restores_it(): void
    {
        $deleted = User::factory()->create(['email' => 'siti@merdekabattery.com']);
        $deleted->delete();

        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.id', $deleted->id);

        $this->assertNotSoftDeleted($deleted);
        $this->assertSame(1, User::withTrashed()->where('email', 'siti@merdekabattery.com')->count());
    }

    public function test_only_a_it_admin_can_assign_the_it_admin_role(): void
    {
        $payload = $this->payload(['role_id' => [(int) config('merdeka.it_admin_role_id')]]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id.0' => 'Only an IT admin can assign the IT Admin role.']);

        $this->actingAs($this->itAdmin())
            ->postJson(route('user-management.user-registered.store'), $payload)
            ->assertCreated();
    }

    public function test_non_it_admin_cannot_edit_a_it_admin_account(): void
    {
        $superAdmin = $this->itAdmin();

        $this->actingAs($this->superAdmin())
            ->getJson(route('user-management.user-registered.show', $superAdmin))
            ->assertForbidden();

        $this->actingAs($this->superAdmin())
            ->putJson(route('user-management.user-registered.update', $superAdmin), $this->payload())
            ->assertForbidden();
    }

    public function test_company_scoped_admin_only_manages_users_of_its_companies(): void
    {
        $otherCompany = Company::factory()->create();
        $insider = User::factory()->create(['company_id' => $this->company->id, 'name' => 'Insider']);
        $outsider = User::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Outsider']);
        $admin = $this->companyScopedAdmin();

        $this->actingAs($admin)
            ->getJson(route('user-management.user-registered.list'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Insider'])
            ->assertJsonMissing(['name' => 'Outsider']);

        $this->actingAs($admin)
            ->getJson(route('user-management.user-registered.show', $outsider))
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('user-management.user-registered.store'), $this->payload(['company_id' => $otherCompany->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);

        $this->assertModelExists($insider);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->itAdmin();

        $this->actingAs($admin)
            ->deleteJson(route('user-management.user-registered.destroy', $admin))
            ->assertUnprocessable();

        $this->assertNotSoftDeleted($admin);
    }

    public function test_deleting_a_user_revokes_roles_and_ends_their_session(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->itAdmin())
            ->deleteJson(route('user-management.user-registered.destroy', $employee))
            ->assertOk();

        $this->assertSoftDeleted($employee);
        $this->assertSame(0, $employee->roles()->count());
    }

    public function test_deactivated_user_is_signed_out_on_the_next_request(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)->get(route('survey-form.index'))->assertOk();

        $employee->update(['is_active' => false]);

        $this->actingAs($employee)
            ->get(route('survey-form.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * Valid registration payload.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return [
            'name' => 'Siti Rahma',
            'email' => 'siti@merdekabattery.com',
            'employee_id' => 'MBM-1001',
            'company_id' => $this->company->id,
            'is_active' => 1,
            'role_id' => [(int) config('merdeka.default_role_id')],
            ...$overrides,
        ];
    }

    /**
     * Super admin limited to the test company.
     */
    protected function companyScopedAdmin(): User
    {
        $role = Role::query()->where('name', 'Super Admin')->sole();
        $role->update(['func_role' => 'COMPANY', 'setting_role' => [$this->company->id]]);

        return User::factory()->withRole($role)->create(['company_id' => $this->company->id]);
    }
}
