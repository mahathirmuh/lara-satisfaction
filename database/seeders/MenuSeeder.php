<?php

namespace Database\Seeders;

use App\Models\UserManagement\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Sidebar menus. Every module page is registered here so the permission
 * middleware can map its routes to the RBAC matrix.
 */
class MenuSeeder extends Seeder
{
    /**
     * Menu tree: root menus with optional children.
     *
     * @var array<int, array{title: string, route?: string, icon?: string, section?: string, children?: array<int, array{title: string, route: string, icon?: string}>}>
     */
    public const MENUS = [
        ['title' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'dashboard'],
        ['title' => 'My Survey', 'route' => 'survey-form.index', 'icon' => 'clipboard-check'],
        [
            'title' => 'Survey Management',
            'icon' => 'clipboard-list',
            'section' => 'Survey',
            'children' => [
                ['title' => 'Survey Period', 'route' => 'survey-management.survey.index'],
                ['title' => 'Questions', 'route' => 'survey-management.question.index'],
                ['title' => 'Reminders', 'route' => 'survey-management.reminder.index'],
            ],
        ],
        ['title' => 'Survey Report', 'route' => 'survey-report.index', 'icon' => 'chart-bar', 'section' => 'Survey'],
        ['title' => 'Survey Progress', 'route' => 'survey-progress.index', 'icon' => 'progress-check', 'section' => 'Survey'],
        [
            'title' => 'User Management',
            'icon' => 'shield-chevron',
            'section' => 'User',
            'children' => [
                ['title' => 'User Registered', 'route' => 'user-management.user-registered.index'],
                ['title' => 'Role & Permission', 'route' => 'user-management.role-permission.index'],
            ],
        ],
        [
            'title' => 'Master Data',
            'icon' => 'database',
            'section' => 'Settings',
            'children' => [
                ['title' => 'Departments', 'route' => 'master-data.department.index'],
                ['title' => 'Sections', 'route' => 'master-data.section.index'],
            ],
        ],
        // Who signed in and who changed what (IT Admin: every menu; other roles get it in Role & Permission).
        ['title' => 'Audit Log', 'route' => 'audit-log.index', 'icon' => 'shield-search', 'section' => 'Settings'],
        [
            'title' => 'System Log',
            'icon' => 'history',
            'section' => 'Settings',
            'children' => [
                ['title' => 'Activity Log', 'route' => 'system-log.activity.index'],
                ['title' => 'Error Log', 'route' => 'system-log.error.index'],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::MENUS as $rootOrder => $root) {
            $parent = $this->upsert($root, null, $rootOrder + 1);

            foreach ($root['children'] ?? [] as $childOrder => $child) {
                $this->upsert($child, $parent->id, $childOrder + 1);
            }
        }

        Cache::forget(Menu::ROUTE_KEY_CACHE);
    }

    /**
     * Create or update a menu identified by its route (or title for groups).
     *
     * @param  array{title: string, route?: string, icon?: string, section?: string}  $menu
     */
    protected function upsert(array $menu, ?int $parentId, int $order): Menu
    {
        $key = isset($menu['route'])
            ? ['route' => $menu['route']]
            : ['title' => $menu['title'], 'parent_id' => $parentId];

        return Menu::query()->updateOrCreate($key, [
            'parent_id' => $parentId,
            'title' => $menu['title'],
            'route' => $menu['route'] ?? null,
            'icon' => $menu['icon'] ?? null,
            'sort_order' => $order,
            'section' => $menu['section'] ?? null,
        ]);
    }
}
