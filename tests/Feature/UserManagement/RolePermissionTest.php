<?php

namespace Tests\Feature\UserManagement;

use App\Enums\PermissionAction;
use App\Models\Organization\Company;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->itAdmin();
    }

    public function test_role_is_created_with_menu_access_and_crud_matrix(): void
    {
        $reportMenu = Menu::query()->where('route', 'survey-report.index')->sole();
        $questionMenu = Menu::query()->where('route', 'survey-management.question.index')->sole();
        $dashboardMenu = Menu::query()->where('route', 'dashboard.index')->sole();

        $this->actingAs($this->admin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Report Viewer',
                'func_role' => 'ALL',
                'department_scope' => 'ALL',
                'home_page' => $reportMenu->id,
                'menu_list' => [$reportMenu->id, $questionMenu->id],
                'menu_permission' => [
                    $reportMenu->id => [PermissionAction::Read->value],
                    $dashboardMenu->id => [PermissionAction::Read->value],
                ],
            ])
            ->assertCreated();

        $role = Role::query()->where('name', 'Report Viewer')->sole();

        $this->assertEqualsCanonicalizing(
            [$reportMenu->id, $questionMenu->id, $questionMenu->parent_id],
            $role->menus()->pluck('menu.id')->all(),
            'The parent group of a granted menu must be granted too.'
        );
        $this->assertSame(
            [[$reportMenu->id, PermissionAction::Read->value]],
            MenuRolePermission::query()->whereBelongsTo($role)->get()->map(fn ($row) => [$row->menu_id, $row->permission_id])->all(),
            'Permissions on menus without access must be dropped.'
        );
        $this->assertSame($reportMenu->id, $role->home_page);
    }

    public function test_company_scoped_role_requires_companies(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Site HR',
                'func_role' => 'COMPANY',
                'department_scope' => 'ALL',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['setting_role']);

        $company = Company::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Site HR',
                'func_role' => 'COMPANY',
                'department_scope' => 'ALL',
                'setting_role' => [$company->id],
            ])
            ->assertCreated();

        $this->assertSame([$company->id], Role::query()->where('name', 'Site HR')->sole()->companyIds());
    }

    public function test_granted_permissions_take_effect_for_the_role_users(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->withRole($role)->create();
        $reportMenu = Menu::query()->where('route', 'survey-report.index')->sole();

        $this->actingAs($user)->get(route('survey-report.index'))->assertForbidden();

        $this->actingAs($this->admin)
            ->putJson(route('user-management.role-permission.update', $role), [
                'name' => $role->name,
                'func_role' => 'ALL',
                'department_scope' => 'ALL',
                'menu_list' => [$reportMenu->id],
                'menu_permission' => [$reportMenu->id => [PermissionAction::Read->value]],
            ])
            ->assertOk();

        $user->flushAccessCache();

        $this->actingAs($user)->get(route('survey-report.index'))->assertOk();
    }

    public function test_only_the_it_admin_can_change_the_it_admin_role(): void
    {
        $itAdminRole = Role::query()->findOrFail((int) config('merdeka.it_admin_role_id'));
        $delegate = $this->userWithFullUserManagement();

        $this->actingAs($delegate)
            ->putJson(route('user-management.role-permission.update', $itAdminRole), ['name' => 'Hijacked', 'func_role' => 'ALL'])
            ->assertForbidden();

        $this->actingAs($delegate)
            ->getJson(route('user-management.role-permission.list'))
            ->assertOk()
            ->assertJsonMissing(['name' => 'IT Admin']);

        $this->assertSame('IT Admin', $itAdminRole->fresh()->name);
    }

    public function test_built_in_roles_and_roles_in_use_cannot_be_deleted(): void
    {
        $inUse = Role::factory()->create();
        User::factory()->withRole($inUse)->create();

        foreach ([config('merdeka.it_admin_role_id'), config('merdeka.default_role_id'), $inUse->id] as $roleId) {
            $this->actingAs($this->admin)
                ->deleteJson(route('user-management.role-permission.destroy', $roleId))
                ->assertUnprocessable();
        }

        $unused = Role::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('user-management.role-permission.destroy', $unused))
            ->assertOk();

        $this->assertModelMissing($unused);
    }

    public function test_job_title_rules_give_the_role_to_existing_users_at_once(): void
    {
        $supervisor = $this->employee(['job_title' => 'IT System & Support Supervisor']);
        $officer = $this->employee(['job_title' => 'IT Support Officer']);

        $this->actingAs($this->admin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Supervisor',
                'func_role' => 'ALL',
                'department_scope' => 'MANAGED',
                'job_titles' => "*Supervisor\n\n  !OHS *  ",
            ])
            ->assertCreated();

        $role = Role::query()->where('name', 'Supervisor')->sole();
        $this->assertSame(['*Supervisor', '!OHS *'], $role->job_titles);

        $this->assertSame(['Supervisor'], $supervisor->fresh()->roles->pluck('name')->all());
        $this->assertSame(['Employee'], $officer->fresh()->roles->pluck('name')->all());

        $this->actingAs($this->admin)
            ->getJson(route('user-management.role-permission.show', $role))
            ->assertOk()
            ->assertJsonPath('data.job_titles', "*Supervisor\n!OHS *")
            ->assertJsonPath('data.department_scope', 'MANAGED');
    }

    public function test_it_admin_role_is_never_given_by_job_title(): void
    {
        $itAdminRole = Role::query()->findOrFail((int) config('merdeka.it_admin_role_id'));

        $this->actingAs($this->admin)
            ->putJson(route('user-management.role-permission.update', $itAdminRole), [
                'name' => 'IT Admin',
                'func_role' => 'ALL',
                'department_scope' => 'ALL',
                'job_titles' => 'IT Support Officer',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['job_titles' => 'The IT Admin role is only given by hand, not by job title.']);

        $this->assertNull($itAdminRole->fresh()->job_titles);
    }

    /**
     * A non IT admin with full CRUD on Role & Permission.
     */
    protected function userWithFullUserManagement(): User
    {
        $role = Role::factory()->create();
        $menu = Menu::query()->where('route', 'user-management.role-permission.index')->sole();
        $role->menus()->sync([$menu->id, $menu->parent_id]);

        foreach (PermissionAction::cases() as $action) {
            MenuRolePermission::create(['menu_id' => $menu->id, 'role_id' => $role->id, 'permission_id' => $action->value]);
        }

        return User::factory()->withRole($role)->create();
    }
}
