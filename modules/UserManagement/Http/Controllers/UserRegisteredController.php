<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Company;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\UserManagement\Http\Requests\UserRegisteredRequest;
use Modules\UserManagement\Repositories\UserRegisteredInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registered users, limited to the companies of the administrator's role scope.
 */
class UserRegisteredController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected UserRegisteredInterface $users) {}

    /**
     * User page.
     */
    public function index(Request $request): View
    {
        $actor = $request->user();

        return view('UserManagement::user-registered.index', [
            'companies' => $this->companiesFor($actor),
            'roles' => $this->assignableRoles($actor),
            'can' => $actor->menuAbilities('user-management.user-registered'),
        ]);
    }

    /**
     * Users for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $actor = $request->user();
        $params = Apps::tableParams($request, ['name', 'email', 'employee_id', 'is_active', 'last_login_at'], 'name');

        $users = $this->users->paginate($params, [
            'company' => array_map('intval', Arr::wrap($request->query('company', []))),
            'role' => array_map('intval', Arr::wrap($request->query('role', []))),
            'is_active' => in_array($request->query('is_active'), ['0', '1'], true) ? $request->query('is_active') : null,
        ], $actor->scopedCompanyIds(), $actor->isSuperAdmin());

        return Apps::tableResponse($users, fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'company' => $user->company?->name,
            'role' => $user->roles->pluck('name')->implode(', '),
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'is_self' => $user->is($actor),
        ]);
    }

    /**
     * A user (JSON for the edit modal).
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureManageable($request->user(), $user);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'company_id' => $user->company_id,
                'is_active' => $user->is_active,
                'role_id' => $user->roles()->pluck('roles.id'),
            ],
        ]);
    }

    /**
     * Register a user.
     */
    public function store(UserRegisteredRequest $request): JsonResponse
    {
        $user = $this->users->create(
            $request->safe()->only(['name', 'email', 'employee_id', 'company_id', 'is_active']),
            $request->validated('role_id'),
        );

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => ['id' => $user->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a user.
     */
    public function update(UserRegisteredRequest $request, User $user): JsonResponse
    {
        $this->ensureManageable($request->user(), $user);

        $this->users->update(
            $user,
            $request->safe()->only(['name', 'email', 'employee_id', 'company_id', 'is_active']),
            $request->validated('role_id'),
        );

        return response()->json(['message' => 'User updated successfully.']);
    }

    /**
     * Remove a user's access.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $user);

        if ($user->is($actor)) {
            return response()->json(['message' => 'You cannot delete your own account.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->users->delete($user);

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /**
     * Abort unless the administrator may manage the user.
     */
    protected function ensureManageable(User $actor, User $user): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $allowedCompanyIds = $actor->scopedCompanyIds();

        abort_if($user->isSuperAdmin(), Response::HTTP_FORBIDDEN, 'Only a super admin can manage this account.');
        abort_if(
            $allowedCompanyIds !== null && ! in_array((int) $user->company_id, $allowedCompanyIds, true),
            Response::HTTP_FORBIDDEN,
            'This user belongs to a company outside your scope.'
        );
    }

    /**
     * Companies the administrator may assign.
     *
     * @return Collection<int, Company>
     */
    protected function companiesFor(User $actor): Collection
    {
        $allowedCompanyIds = $actor->scopedCompanyIds();

        return Company::query()
            ->when($allowedCompanyIds !== null, fn ($query) => $query->whereIn('id', $allowedCompanyIds))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    /**
     * Roles the administrator may assign.
     *
     * @return Collection<int, Role>
     */
    protected function assignableRoles(User $actor): Collection
    {
        return Role::query()
            ->unless($actor->isSuperAdmin(), fn ($query) => $query->whereKeyNot((int) config('merdeka.super_admin_role_id')))
            ->orderBy('id')
            ->get(['id', 'name', 'func_role']);
    }
}
