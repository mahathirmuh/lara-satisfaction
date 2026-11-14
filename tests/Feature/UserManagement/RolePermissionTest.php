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

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->superAdmin();
    }

    public function test_role_is_created_with_menu_access_and_crud_matrix(): void
    {
        $reportMenu = Menu::query()->where('route', 'survey-report.index')->sole();
        $questionMenu = Menu::query()->where('route', 'survey-management.question.index')->sole();
        $dashboardMenu = Menu::query()->where('route', 'dashboard.index')->sole();

        $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Report Viewer',
                'func_role' => 'ALL',
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
        $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Site HR',
                'func_role' => 'COMPANY',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['setting_role']);

        $company = Company::factory()->create();

        $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.role-permission.store'), [
                'name' => 'Site HR',
                'func_role' => 'COMPANY',
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

        $this->actingAs($this->superAdmin)
            ->putJson(route('user-management.role-permission.update', $role), [
                'name' => $role->name,
                'func_role' => 'ALL',
                'menu_list' => [$reportMenu->id],
                'menu_permission' => [$reportMenu->id => [PermissionAction::Read->value]],
            ])
            ->assertOk();

        $user->flushAccessCache();

        $this->actingAs($user)->get(route('survey-report.index'))->assertOk();
    }

    public function test_only_the_super_admin_can_change_the_super_admin_role(): void
    {
        $superAdminRole = Role::query()->findOrFail((int) config('merdeka.super_admin_role_id'));
        $delegate = $this->userWithFullUserManagement();

        $this->actingAs($delegate)
            ->putJson(route('user-management.role-permission.update', $superAdminRole), ['name' => 'Hijacked', 'func_role' => 'ALL'])
            ->assertForbidden();

        $this->actingAs($delegate)
            ->getJson(route('user-management.role-permission.list'))
            ->assertOk()
            ->assertJsonMissing(['name' => 'Super Admin']);

        $this->assertSame('Super Admin', $superAdminRole->fresh()->name);
    }

    public function test_built_in_roles_and_roles_in_use_cannot_be_deleted(): void
    {
        $inUse = Role::factory()->create();
        User::factory()->withRole($inUse)->create();

        foreach ([config('merdeka.super_admin_role_id'), config('merdeka.default_role_id'), $inUse->id] as $roleId) {
            $this->actingAs($this->superAdmin)
                ->deleteJson(route('user-management.role-permission.destroy', $roleId))
                ->assertUnprocessable();
        }

        $unused = Role::factory()->create();

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('user-management.role-permission.destroy', $unused))
            ->assertOk();

        $this->assertModelMissing($unused);
    }

    /**
     * A non super admin with full CRUD on Role & Permission.
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
