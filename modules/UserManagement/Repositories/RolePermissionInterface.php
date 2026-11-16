<?php

namespace Modules\UserManagement\Repositories;

use App\Models\UserManagement\Role;
use Illuminate\Pagination\LengthAwarePaginator;

interface RolePermissionInterface
{
    /**
     * Paginated roles with user and menu counts.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(array $params, bool $includeItAdmin): LengthAwarePaginator;

    /**
     * Create a role with its menu access and CRUD matrix.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $menuIds
     * @param  array<int|string, array<int, int|string>>  $permissionMatrix  menu id => permission ids
     */
    public function create(array $data, array $menuIds, array $permissionMatrix): Role;

    /**
     * Update a role with its menu access and CRUD matrix.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $menuIds
     * @param  array<int|string, array<int, int|string>>  $permissionMatrix
     */
    public function update(Role $role, array $data, array $menuIds, array $permissionMatrix): Role;

    /**
     * Delete a role.
     */
    public function delete(Role $role): void;
}
