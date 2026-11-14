<?php

namespace Modules\UserManagement\Repositories;

use App\Models\UserManagement\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRegisteredInterface
{
    /**
     * Paginated users visible within a company scope (null = every company).
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{company?: array<int, int>, role?: array<int, int>, is_active?: string|null}  $filters
     * @param  array<int, int>|null  $companyIds
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $params, array $filters, ?array $companyIds, bool $includeSuperAdmins): LengthAwarePaginator;

    /**
     * Register a user (restoring a previously deleted account with the same email).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function create(array $data, array $roleIds): User;

    /**
     * Update a user and its roles.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function update(User $user, array $data, array $roleIds): User;

    /**
     * Remove a user's access (soft delete).
     */
    public function delete(User $user): void;
}
