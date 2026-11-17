<?php

namespace Tests\Feature\AccessControl;

use App\Enums\PermissionAction;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\SurveySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MenuPermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedPages(): array
    {
        return [
            'dashboard' => ['dashboard.index'],
            'my survey' => ['survey-form.index'],
            'survey period' => ['survey-management.survey.index'],
            'survey report' => ['survey-report.index'],
            'user registered' => ['user-management.user-registered.index'],
            'activity log' => ['system-log.activity.index'],
        ];
    }

    #[DataProvider('protectedPages')]
    public function test_guests_are_redirected_to_the_login_page(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function employeePages(): array
    {
        return [
            'dashboard' => ['dashboard.index', 200],
            'my survey' => ['survey-form.index', 200],
            'survey period' => ['survey-management.survey.index', 403],
            'questions' => ['survey-management.question.index', 403],
            'survey report' => ['survey-report.index', 403],
            'user registered' => ['user-management.user-registered.index', 403],
            'role permission' => ['user-management.role-permission.index', 403],
            'activity log' => ['system-log.activity.index', 403],
            'departments' => ['master-data.department.index', 403],
        ];
    }

    #[DataProvider('employeePages')]
    public function test_employee_only_reaches_the_menus_granted_to_the_role(string $routeName, int $status): void
    {
        $this->actingAs($this->employee())->get(route($routeName))->assertStatus($status);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function superAdminPages(): array
    {
        return [
            'survey period' => ['survey-management.survey.index', 200],
            'questions' => ['survey-management.question.index', 200],
            'survey report' => ['survey-report.index', 200],
            'user registered' => ['user-management.user-registered.index', 200],
            'departments' => ['master-data.department.index', 200],
            'sections' => ['master-data.section.index', 200],
            'role permission' => ['user-management.role-permission.index', 403],
            'error log' => ['system-log.error.index', 403],
        ];
    }

    #[DataProvider('superAdminPages')]
    public function test_super_admin_reaches_the_survey_menus_only(string $routeName, int $status): void
    {
        $this->actingAs($this->superAdmin())->get(route($routeName))->assertStatus($status);
    }

    public function test_menu_access_without_read_opens_the_page_but_not_its_data(): void
    {
        $user = $this->userWithGrants(['survey-management.survey.index' => []]);

        $this->actingAs($user)->get(route('survey-management.survey.index'))->assertOk();
        $this->actingAs($user)->getJson(route('survey-management.survey.list'))->assertForbidden();
    }

    public function test_read_permission_does_not_allow_creating_records(): void
    {
        $user = $this->userWithGrants(['survey-management.survey.index' => [PermissionAction::Read]]);

        $this->actingAs($user)->getJson(route('survey-management.survey.list'))->assertOk();
        $this->actingAs($user)->postJson(route('survey-management.survey.store'), [])->assertForbidden();
    }

    public function test_routes_not_registered_as_a_menu_are_closed_to_everyone_but_the_it_admin(): void
    {
        Route::middleware(['web', 'auth', 'permission'])
            ->get('unregistered-module', fn () => 'reachable')
            ->name('unregistered-module.index');

        $this->actingAs($this->employee())->get('unregistered-module')->assertForbidden();
        $this->actingAs($this->itAdmin())->get('unregistered-module')->assertOk()->assertSee('reachable');
    }

    public function test_sidebar_lists_only_the_menus_of_the_role(): void
    {
        $this->actingAs($this->employee())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('My Survey')
            ->assertDontSee('Survey Management')
            ->assertDontSee('User Management')
            ->assertDontSee('System Log');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function menuPages(): array
    {
        $pages = [];

        foreach (MenuSeeder::MENUS as $menu) {
            foreach ([$menu, ...($menu['children'] ?? [])] as $item) {
                if (isset($item['route'])) {
                    $pages[$item['title']] = [$item['route']];
                }
            }
        }

        return $pages;
    }

    #[DataProvider('menuPages')]
    public function test_it_admin_opens_every_menu_page(string $routeName): void
    {
        $this->seed([CompanySeeder::class, DepartmentSeeder::class, SurveySeeder::class]);

        $this->actingAs($this->itAdmin())
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('<title>', false);
    }

    public function test_it_admin_sees_every_menu(): void
    {
        $this->actingAs($this->itAdmin())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Survey Management')
            ->assertSee('Role &amp; Permission', false)
            ->assertSee('Error Log');
    }

    /**
     * A user whose only role grants the given menu routes with the given permissions.
     *
     * @param  array<string, array<int, PermissionAction>>  $grants
     */
    protected function userWithGrants(array $grants): User
    {
        $this->seedAccessControl();
        $role = Role::factory()->create();

        foreach ($grants as $route => $permissions) {
            $menu = Menu::query()->where('route', $route)->sole();
            $role->menus()->syncWithoutDetaching(array_filter([$menu->id, $menu->parent_id]));

            foreach ($permissions as $permission) {
                MenuRolePermission::create(['menu_id' => $menu->id, 'role_id' => $role->id, 'permission_id' => $permission->value]);
            }
        }

        return User::factory()->withRole($role)->create();
    }
}
