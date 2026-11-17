<?php

namespace Modules\UserManagement\Repositories;

use App\Models\UserManagement\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRegisteredInterface
{
    /**
     * Paginated users (IT admins only for an IT admin).
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{company?: array<int, int>, department?: array<int, int>, role?: array<int, int>, is_active?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $params, array $filters, bool $includeItAdmins): LengthAwarePaginator;

    /**
     * Register a user (restoring a previously deleted account with the same
     * email). The given roles are the ones assigned by hand; the roles given
     * by job title are added from the user's job title. Only an IT Admin
     * ($byItAdmin) gives a role of the job title by hand (JobTitleRoles::assign()).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function create(array $data, array $roleIds, bool $byItAdmin): User;

    /**
     * Update a user and the roles assigned by hand; the roles given by job
     * title follow the user's job title. Only an IT Admin ($byItAdmin) gives
     * a role of the job title by hand (JobTitleRoles::assign()).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function update(User $user, array $data, array $roleIds, bool $byItAdmin): User;

    /**
     * Remove a user's access (soft delete).
     */
    public function delete(User $user): void;
}
