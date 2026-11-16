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
    public function paginate(array $params, array $filters, ?array $companyIds, bool $includeItAdmins): LengthAwarePaginator;

    /**
     * Register a user (restoring a previously deleted account with the same
     * email). The given roles are the ones assigned by hand; the roles given
     * by job title are added from the user's job title.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function create(array $data, array $roleIds): User;

    /**
     * Update a user and the roles assigned by hand; the roles given by job
     * title follow the user's job title.
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
