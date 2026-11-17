<?php

namespace Tests\Feature\UserManagement;

use App\Models\Organization\Company;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use App\Services\Auth\JobTitleRoles;
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
                'job_title' => '  Support Services   Deputy General Manager ',
                'role_id' => [$employeeRoleId, $auditor->id],
            ]))
            ->assertCreated();

        $user = User::query()->where('email', 'siti@merdekabattery.com')->sole();
        $this->assertSame('Support Services Deputy General Manager', $user->job_title);
        $this->assertSame(['Super Admin', 'Auditor'], $user->roles->sortBy('id')->pluck('name')->values()->all());

        $this->actingAs($this->superAdmin())
            ->putJson(route('user-management.user-registered.update', $user), $this->payload([
                'job_title' => 'Safety Officer',
                'role_id' => [],
            ]))
            ->assertOk();

        $this->assertSame(['Employee'], $user->fresh()->roles->pluck('name')->all());
    }

    public function test_an_it_admin_gives_a_job_title_role_by_hand_and_it_stays_whatever_the_job_title(): void
    {
        $superAdminRoleId = Role::query()->where('name', 'Super Admin')->value('id');
        $employeeRoleId = (int) config('merdeka.default_role_id');
        $itAdmin = $this->itAdmin();

        $this->actingAs($itAdmin)
            ->postJson(route('user-management.user-registered.store'), $this->payload([
                'job_title' => 'IT System & Support Supervisor',
                'role_id' => [$superAdminRoleId],
            ]))
            ->assertCreated();

        $user = User::query()->where('email', 'siti@merdekabattery.com')->sole();
        app(JobTitleRoles::class)->sync($user);

        $this->assertSame(['Super Admin', 'Employee'], $user->fresh()->roles->sortBy('id')->pluck('name')->values()->all());
        $this->actingAs($itAdmin)
            ->getJson(route('user-management.user-registered.show', $user))
            ->assertOk()
            ->assertJsonPath('data.role_id', [$superAdminRoleId])
            ->assertJsonPath('data.job_title_role_id', [$employeeRoleId]);

        $this->actingAs($itAdmin)
            ->putJson(route('user-management.user-registered.update', $user), $this->payload([
                'job_title' => 'IT System & Support Supervisor',
                'role_id' => [],
            ]))
            ->assertOk();

        $this->assertSame(['Employee'], $user->fresh()->roles->pluck('name')->all(), 'Taken back, the role follows the job title again.');
    }

    public function test_only_an_it_admin_gives_a_job_title_role_by_hand_and_others_keep_what_it_gave(): void
    {
        $superAdminRoleId = Role::query()->where('name', 'Super Admin')->value('id');
        $payload = $this->payload(['job_title' => 'IT System & Support Supervisor', 'role_id' => [$superAdminRoleId]]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('user-management.user-registered.store'), $payload)
            ->assertCreated();

        $user = User::query()->where('email', 'siti@merdekabattery.com')->sole();
        $this->assertSame(['Employee'], $user->roles->pluck('name')->all());

        $this->actingAs($this->itAdmin())
            ->putJson(route('user-management.user-registered.update', $user), $payload)
            ->assertOk();
        $this->actingAs($this->superAdmin())
            ->putJson(route('user-management.user-registered.update', $user), [...$payload, 'name' => 'Siti R.', 'role_id' => []])
            ->assertOk();

        $this->assertSame(['Super Admin', 'Employee'], $user->fresh()->roles->sortBy('id')->pluck('name')->values()->all());
    }

    public function test_the_job_title_roles_are_open_in_the_form_for_an_it_admin_only(): void
    {
        $this->actingAs($this->itAdmin())
            ->get(route('user-management.user-registered.index'))
            ->assertOk()
            ->assertViewHas('canGiveJobTitleRoles', true)
            ->assertSee('As IT Admin you may also give one by hand');

        $this->actingAs($this->superAdmin())
            ->get(route('user-management.user-registered.index'))
            ->assertOk()
            ->assertViewHas('canGiveJobTitleRoles', false)
            ->assertDontSee('As IT Admin you may also give one by hand');
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

    public function test_admin_manages_users_of_every_company(): void
    {
        $otherCompany = Company::factory()->create();
        User::factory()->create(['company_id' => $this->company->id, 'name' => 'Insider']);
        $outsider = User::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Outsider']);
        $admin = User::factory()->withRole(Role::query()->where('name', 'Super Admin')->sole())->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('user-management.user-registered.list'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Insider'])
            ->assertJsonFragment(['name' => 'Outsider']);

        $this->actingAs($admin)
            ->getJson(route('user-management.user-registered.show', $outsider))
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(route('user-management.user-registered.store'), $this->payload(['company_id' => $otherCompany->id]))
            ->assertCreated();
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
}
