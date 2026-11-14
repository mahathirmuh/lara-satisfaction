<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Enums\RoleScope;
use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Company;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\Permission;
use App\Models\UserManagement\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\UserManagement\Http\Requests\RolePermissionRequest;
use Modules\UserManagement\Repositories\RolePermissionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roles with menu access (ACCESS) and per-menu CRUD permissions.
 */
class RolePermissionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected RolePermissionInterface $roles) {}

    /**
     * Role & permission page.
     */
    public function index(Request $request): View
    {
        return view('UserManagement::role-permission.index', [
            'menus' => Menu::query()
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'permissions' => Permission::query()->orderBy('id')->get(['id', 'name']),
            'companies' => Company::query()->orderBy('name')->get(['id', 'code', 'name']),
            'scopes' => RoleScope::options(),
            'can' => $request->user()->menuAbilities('user-management.role-permission'),
        ]);
    }

    /**
     * Roles for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $actor = $request->user();
        $params = Apps::tableParams($request, ['name', 'func_role', 'users_count'], 'id');
        $companyNames = Company::query()->pluck('name', 'id');

        $roles = $this->roles->paginate($params, $actor->isSuperAdmin());

        return Apps::tableResponse($roles, fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'func_role' => $role->func_role->value,
            'scope' => $role->func_role === RoleScope::All
                ? 'All companies'
                : collect($role->companyIds())->map(fn (int $id): string => $companyNames[$id] ?? '#'.$id)->implode(', '),
            'users_count' => $role->users_count,
            'menus_count' => $role->menus_count,
            'is_protected' => $role->isSuperAdmin(),
            'is_default' => $role->id === (int) config('merdeka.default_role_id'),
        ]);
    }

    /**
     * A role with its grants (JSON for the edit modal).
     */
    public function show(Request $request, Role $role): JsonResponse
    {
        abort_if($role->isSuperAdmin() && ! $request->user()->isSuperAdmin(), Response::HTTP_FORBIDDEN);

        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'func_role' => $role->func_role->value,
                'setting_role' => $role->companyIds(),
                'home_page' => $role->home_page,
                'menu_list' => $role->menus()->pluck('menu.id'),
                'menu_permission' => $role->menuPermissions()
                    ->get(['menu_id', 'permission_id'])
                    ->groupBy('menu_id')
                    ->map(fn ($rows) => $rows->pluck('permission_id')->values()),
            ],
        ]);
    }

    /**
     * Create a role.
     */
    public function store(RolePermissionRequest $request): JsonResponse
    {
        $role = $this->roles->create(
            $request->roleAttributes(),
            $request->validated('menu_list') ?? [],
            $request->validated('menu_permission') ?? [],
        );

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => ['id' => $role->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a role.
     */
    public function update(RolePermissionRequest $request, Role $role): JsonResponse
    {
        $this->roles->update(
            $role,
            $request->roleAttributes(),
            $request->validated('menu_list') ?? [],
            $request->validated('menu_permission') ?? [],
        );

        return response()->json(['message' => 'Role updated successfully.']);
    }

    /**
     * Delete a role that is not protected and has no users.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->isSuperAdmin() || $role->id === (int) config('merdeka.default_role_id')) {
            return response()->json(['message' => 'This built-in role cannot be deleted.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'This role is still assigned to users.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->roles->delete($role);

        return response()->json(['message' => 'Role deleted successfully.']);
    }
}
