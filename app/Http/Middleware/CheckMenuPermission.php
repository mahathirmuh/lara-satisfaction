<?php

namespace App\Http\Middleware;

use App\Enums\PermissionAction;
use App\Models\UserManagement\Menu;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the menu/role/permission (RBAC) matrix for every route tied to a menu,
 * so controllers do not need to call guardRole()/hasMenuPermission() by hand.
 *
 * Resolution of a request:
 *   1. route name                 "user-management.user-registered.store"
 *   2. menu key (without action)  "user-management.user-registered"
 *   3. the Menu whose route shares that key
 *   4. ACCESS check (menu_role) + CRUD check (menu_role_permission) by HTTP verb
 *
 * Policy: DEFAULT CLOSED. A route guarded by this middleware that is not tied
 * to a registered menu is only reachable by the super admin, so a new module
 * cannot be exposed by forgetting to register its menu.
 */
class CheckMenuPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, Response::HTTP_UNAUTHORIZED);

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $menuId = $routeName !== null ? Menu::idForRoute($routeName) : null;

        abort_if($menuId === null, Response::HTTP_FORBIDDEN, 'This page is not registered in the menu configuration.');

        abort_unless(
            $user->guardRole($menuId),
            Response::HTTP_FORBIDDEN,
            'You do not have access to this menu.'
        );

        $permission = static::requiredPermission($routeName, $request->method());

        if ($permission !== null) {
            abort_unless(
                $user->hasMenuPermission($menuId, $permission),
                Response::HTTP_FORBIDDEN,
                'You are not allowed to perform this action.'
            );
        }

        return $next($request);
    }

    /**
     * Permission a route requires, or null when only ACCESS is needed.
     *
     * Opening a menu page (*.index) requires only ACCESS; loading its data
     * (GET) requires Read and mutations require Create/Update/Delete.
     */
    public static function requiredPermission(string $routeName, string $method): ?PermissionAction
    {
        if (Str::endsWith($routeName, '.index')) {
            return null;
        }

        return PermissionAction::forHttpMethod($method);
    }
}
