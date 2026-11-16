<?php

namespace App\View\Composers;

use App\Models\UserManagement\Menu;
use App\Models\UserManagement\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Supplies the path at the top of a page (main.breadcrumb), as MTI Radio Management draws it: "Dashboard › Survey
 * Management › Reminders". The dashboard comes first for whoever may open it; then the group and the entry of the
 * sidebar the page belongs to (any route of the entry's menu key, as the sidebar marks it: a survey's report belongs to
 * Survey Report); then the page itself when it is deeper than its entry ("Operational Audit 2026"). A page that belongs
 * to no entry (My Profile) is the dashboard and its name.
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
        $sections = $view->getFactory();
        // What the page calls itself (@section('crumb')), else its title. A section is kept escaped: the view escapes again.
        $page = html_entity_decode(trim($sections->yieldContent('crumb') ?: $sections->yieldContent('page-title')), ENT_QUOTES | ENT_HTML5);

        $view->with('crumbs', $this->crumbs((string) $this->request->route()?->getName(), $this->request->user(), $page));
    }

    /**
     * The path to the page, the page that is open last.
     *
     * @param  string  $page  what the page calls itself; '' for nothing
     * @return list<array{label: string, url: ?string}> the open page has no url
     */
    protected function crumbs(string $current, ?User $user, string $page): array
    {
        $entry = $this->entry($current);
        $crumbs = [];

        if ($current !== 'dashboard.index' && $user?->canAccessMenu('dashboard') && Route::has('dashboard.index')) {
            $crumbs[] = ['label' => __('Dashboard'), 'url' => route('dashboard.index')];
        }

        if ($entry === null) {
            return $page === '' ? $crumbs : [...$crumbs, ['label' => $page, 'url' => null]];
        }

        if ($entry->parent !== null) {
            $crumbs[] = ['label' => __($entry->parent->title), 'url' => null];
        }

        // On a page below the entry, the entry leads back to its list, and the page names itself after it.
        $label = __($entry->title);
        $isBelow = $current !== $entry->route && $page !== '' && $page !== $label;
        $crumbs[] = ['label' => $label, 'url' => $isBelow ? route($entry->route) : null];

        if ($isBelow) {
            $crumbs[] = ['label' => $page, 'url' => null];
        }

        return $crumbs;
    }

    /**
     * The entry of the sidebar that the route belongs to: the one with the longest menu key that covers it.
     */
    protected function entry(string $current): ?Menu
    {
        if ($current === '') {
            return null;
        }

        return Menu::query()->whereNotNull('route')->with('parent')->get()
            ->filter(fn (Menu $menu): bool => Route::has($menu->route)
                && ($current === $menu->route || Str::startsWith($current, Menu::keyForRoute($menu->route).'.')))
            ->sortByDesc(fn (Menu $menu): int => strlen(Menu::keyForRoute($menu->route)))
            ->first();
    }
}
