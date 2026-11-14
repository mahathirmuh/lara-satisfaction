<?php

namespace Modules\UserManagement\Repositories;

use App\Helpers\Apps;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RolePermissionRepository implements RolePermissionInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $params, bool $includeSuperAdmin): LengthAwarePaginator
    {
        return Role::query()
            ->withCount(['users', 'menus'])
            ->unless($includeSuperAdmin, fn (Builder $query) => $query->whereKeyNot((int) config('merdeka.super_admin_role_id')))
            ->when($params['search'], fn (Builder $query, string $search) => $query->where('name', 'like', Apps::likePattern($search)))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderBy('id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, array $menuIds, array $permissionMatrix): Role
    {
        return DB::transaction(function () use ($data, $menuIds, $permissionMatrix): Role {
            $role = Role::create($data);
            $this->syncGrants($role, $menuIds, $permissionMatrix);

            return $role;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(Role $role, array $data, array $menuIds, array $permissionMatrix): Role
    {
        return DB::transaction(function () use ($role, $data, $menuIds, $permissionMatrix): Role {
            $role->update($data);
            $this->syncGrants($role, $menuIds, $permissionMatrix);

            return $role;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * Replace the role's menu access and CRUD matrix. Parents of granted menus
     * are granted too (the sidebar only shows a child when its group is
     * granted) and permissions are only kept for granted menus.
     *
     * @param  array<int, int>  $menuIds
     * @param  array<int|string, array<int, int|string>>  $permissionMatrix
     */
    protected function syncGrants(Role $role, array $menuIds, array $permissionMatrix): void
    {
        $menuIds = collect($menuIds)->map(fn ($id): int => (int) $id)->unique();
        $parentIds = Menu::query()->whereKey($menuIds->all())->whereNotNull('parent_id')->pluck('parent_id');
        $grantedMenuIds = $menuIds->merge($parentIds)->map(fn ($id): int => (int) $id)->unique()->values();

        $role->menus()->sync($grantedMenuIds->all());

        MenuRolePermission::query()->whereBelongsTo($role)->delete();

        $now = now();
        $rows = [];

        foreach ($permissionMatrix as $menuId => $permissionIds) {
            if (! $grantedMenuIds->contains((int) $menuId)) {
                continue;
            }

            foreach (array_unique(array_map('intval', (array) $permissionIds)) as $permissionId) {
                $rows[] = [
                    'menu_id' => (int) $menuId,
                    'role_id' => $role->getKey(),
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            MenuRolePermission::query()->insert($rows);
        }
    }
}
