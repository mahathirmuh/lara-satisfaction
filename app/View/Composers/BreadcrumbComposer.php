<?php

namespace App\View\Composers;

use App\Models\UserManagement\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Supplies the breadcrumb (main.breadcrumb) with the menu path of the current
 * page: the menu that owns the route (same mapping as the permission
 * middleware, e.g. survey-report.show => Survey Report) and its parents.
 * Pages add their own items (a survey title...) on the "breadcrumbs" stack.
 */
class BreadcrumbComposer
{
    /**
     * Create a new composer instance.
     */
    public function __construct(protected Request $request) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $view->with([
            'homeUrl' => $this->request->user()?->homeUrl() ?? route('dashboard.index'),
            'trail' => $this->menuTrail((string) $this->request->route()?->getName()),
        ]);
    }

    /**
     * The menu path of the route, root first; the menu whose own page is open
     * is marked current.
     *
     * @return array<int, array{label: string, url: string|null, current: bool}>
     */
    protected function menuTrail(string $routeName): array
    {
        $menuId = $routeName === '' ? null : Menu::idForRoute($routeName);
        $menu = $menuId === null ? null : Menu::query()->with('parent.parent.parent')->find($menuId);
        $trail = [];

        for ($node = $menu; $node !== null; $node = $node->parent) {
            array_unshift($trail, [
                'label' => __($node->title),
                'url' => $node->route !== null && Route::has($node->route) ? route($node->route) : null,
                'current' => $node->is($menu) && $node->route === $routeName,
            ]);
        }

        return $trail;
    }
}
