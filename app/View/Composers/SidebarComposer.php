<?php

namespace App\View\Composers;

use App\Models\UserManagement\Menu;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supplies the database-driven sidebar with the menu tree and the menu ids
 * granted to the signed-in user.
 */
class SidebarComposer
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
            'allowedMenuIds' => $this->request->user()?->grantedMenuIds() ?? [],
            'rootMenus' => Menu::query()
                ->whereNull('parent_id')
                ->with('children.children')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
