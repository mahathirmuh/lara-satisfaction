<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Enums\DepartmentScope;
use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\Permission;
use App\Models\UserManagement\Role;
use App\Services\Auth\JobTitleRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\UserManagement\Http\Requests\RolePermissionRequest;
use Modules\UserManagement\Repositories\RolePermissionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roles with menu access (ACCESS), per-menu CRUD permissions, the departments
 * whose survey results they see and the job title rules that give them.
 * Changing the rules refreshes the roles of every user at once.
 */
class RolePermissionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected RolePermissionInterface $roles,
        protected JobTitleRoles $jobTitleRoles,
    ) {}

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
            'departmentScopes' => DepartmentScope::options(),
            'can' => $request->user()->menuAbilities('user-management.role-permission'),
        ]);
    }

    /**
     * Roles for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $actor = $request->user();
        $params = Apps::tableParams($request, ['name', 'users_count'], 'id');

        $roles = $this->roles->paginate($params, $actor->isItAdmin());

        return Apps::tableResponse($roles, fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'users_count' => $role->users_count,
            'menus_count' => $role->menus_count,
            'job_titles' => $role->isGivenByJobTitle() ? $role->job_titles : [],
            'department_scope' => ($role->department_scope ?? DepartmentScope::All)->label(),
            'is_protected' => $role->isItAdmin(),
            'is_default' => $role->id === (int) config('merdeka.default_role_id'),
        ]);
    }

    /**
     * A role with its grants (JSON for the edit modal).
     */
    public function show(Request $request, Role $role): JsonResponse
    {
        abort_if($role->isItAdmin() && ! $request->user()->isItAdmin(), Response::HTTP_FORBIDDEN);

        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'home_page' => $role->home_page,
                'job_titles' => implode("\n", $role->job_titles ?? []),
                'department_scope' => ($role->department_scope ?? DepartmentScope::All)->value,
                'is_it_admin' => $role->isItAdmin(),
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

        if ($role->isGivenByJobTitle()) {
            $this->jobTitleRoles->syncAll();
        }

        return response()->json([
            'message' => __('Role created successfully.'),
            'data' => ['id' => $role->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a role.
     */
    public function update(RolePermissionRequest $request, Role $role): JsonResponse
    {
        $previousJobTitles = $role->job_titles;

        $this->roles->update(
            $role,
            $request->roleAttributes(),
            $request->validated('menu_list') ?? [],
            $request->validated('menu_permission') ?? [],
        );

        if ($role->job_titles !== $previousJobTitles) {
            $this->jobTitleRoles->syncAll();
        }

        return response()->json(['message' => __('Role updated successfully.')]);
    }

    /**
     * Delete a role that is not protected and has no users.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->isItAdmin() || $role->id === (int) config('merdeka.default_role_id')) {
            return response()->json(['message' => __('This built-in role cannot be deleted.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => __('This role is still assigned to users.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->roles->delete($role);

        return response()->json(['message' => __('Role deleted successfully.')]);
    }
}
