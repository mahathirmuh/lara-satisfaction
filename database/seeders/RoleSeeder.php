<?php

namespace Database\Seeders;

use App\Enums\DepartmentScope;
use App\Enums\PermissionAction;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use App\Services\Auth\JobTitleRoles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Built-in roles with their menu access and CRUD matrix, highest first:
 * IT Admin (everything, only given by hand), Super Admin (survey
 * administration and every result), Manager (fills in the survey and sees the
 * results of the departments they manage) and Employee (fills in the survey).
 *
 * Super Admin and Manager follow the job title from the directory; Employee
 * is given when no rule matches. Role ids 1 (IT Admin) and 3 (Employee) are
 * referenced by config/merdeka.php, so roles are created in this order on an
 * empty table.
 */
class RoleSeeder extends Seeder
{
    /**
     * Role name => [menu route => permissions] ("*" grants everything).
     * Group menus are granted automatically with their children.
     *
     * @var array<string, array<string, array<int, PermissionAction>>|string>
     */
    protected const GRANTS = [
        'IT Admin' => '*',
        'Super Admin' => [
            'dashboard.index' => [PermissionAction::Read],
            'survey-form.index' => [PermissionAction::Create, PermissionAction::Read],
            'survey-management.survey.index' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
            'survey-management.question.index' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
            'survey-management.reminder.index' => [PermissionAction::Create, PermissionAction::Read],
            'survey-report.index' => [PermissionAction::Read],
            'user-management.user-registered.index' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
            'master-data.department.index' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
            'master-data.section.index' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
            'master-data.landing-content.index' => [PermissionAction::Read, PermissionAction::Update],
        ],
        'Employee' => [
            'dashboard.index' => [PermissionAction::Read],
            'survey-form.index' => [PermissionAction::Create, PermissionAction::Read],
        ],
        'Manager' => [
            'dashboard.index' => [PermissionAction::Read],
            'survey-form.index' => [PermissionAction::Create, PermissionAction::Read],
            'survey-report.index' => [PermissionAction::Read],
        ],
    ];

    /**
     * Home page (menu route) per role.
     *
     * @var array<string, string>
     */
    protected const HOME_PAGES = [
        'Employee' => 'survey-form.index',
    ];

    /**
     * Job title rules giving a role at sign-in (see App\Helpers\JobTitles).
     *
     * @var array<string, array<int, string>>
     */
    protected const JOB_TITLES = [
        'Super Admin' => ['Support Services Deputy General Manager'],
        'Manager' => ['*Manager', 'Deputy GM', '!Assistant of *'],
    ];

    /**
     * Roles that only see the survey results of the departments they manage.
     *
     * @var array<int, string>
     */
    protected const MANAGED_DEPARTMENTS_ONLY = ['Manager'];

    /**
     * Run the database seeds.
     */
    public function run(JobTitleRoles $jobTitleRoles): void
    {
        $menus = Menu::query()->get();

        foreach (self::GRANTS as $roleName => $grants) {
            $role = Role::query()->firstOrCreate(['name' => $roleName]);

            $homeRoute = self::HOME_PAGES[$roleName] ?? null;
            $role->update([
                'home_page' => $homeRoute ? $menus->firstWhere('route', $homeRoute)?->id : null,
                'job_titles' => self::JOB_TITLES[$roleName] ?? null,
                'department_scope' => in_array($roleName, self::MANAGED_DEPARTMENTS_ONLY, true) ? DepartmentScope::Managed : DepartmentScope::All,
            ]);

            $this->grant($role, $menus, $grants);
        }

        $this->assertBuiltInIds();

        $jobTitleRoles->syncAll();
    }

    /**
     * Replace the menu access and CRUD matrix of a role.
     *
     * @param  Collection<int, Menu>  $menus
     * @param  array<string, array<int, PermissionAction>>|string  $grants
     */
    protected function grant(Role $role, $menus, array|string $grants): void
    {
        if ($grants === '*') {
            $grants = $menus->whereNotNull('route')
                ->mapWithKeys(fn (Menu $menu): array => [$menu->route => PermissionAction::cases()])
                ->all();
        }

        $menuIds = [];
        $permissionRows = [];

        foreach ($grants as $route => $actions) {
            $menu = $menus->firstWhere('route', $route);

            if ($menu === null) {
                continue;
            }

            $menuIds[] = $menu->id;

            if ($menu->parent_id !== null) {
                $menuIds[] = $menu->parent_id;
            }

            foreach ($actions as $action) {
                $permissionRows[] = [
                    'menu_id' => $menu->id,
                    'role_id' => $role->id,
                    'permission_id' => $action->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $role->menus()->sync(array_values(array_unique($menuIds)));

        MenuRolePermission::query()->whereBelongsTo($role)->delete();
        MenuRolePermission::query()->insert($permissionRows);
    }

    /**
     * Guard the ids referenced by configuration.
     */
    protected function assertBuiltInIds(): void
    {
        $expected = [
            (int) config('merdeka.it_admin_role_id') => 'IT Admin',
            (int) config('merdeka.default_role_id') => 'Employee',
        ];

        foreach ($expected as $id => $name) {
            if (Role::query()->whereKey($id)->value('name') !== $name) {
                throw new RuntimeException("Role [{$name}] must have id {$id}. Seed an empty roles table or update config/merdeka.php.");
            }
        }
    }
}
