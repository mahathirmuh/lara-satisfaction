<?php

namespace Modules\UserManagement\Repositories;

use App\Helpers\Apps;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRegisteredRepository implements UserRegisteredInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $params, array $filters, ?array $companyIds, bool $includeSuperAdmins): LengthAwarePaginator
    {
        $superAdminRoleId = (int) config('merdeka.super_admin_role_id');
        $isActive = $filters['is_active'] ?? null;

        return User::query()
            ->with(['company:id,name,code', 'roles:id,name'])
            ->when($companyIds !== null, fn (Builder $query) => $query->whereIn('company_id', $companyIds))
            ->unless($includeSuperAdmins, fn (Builder $query) => $query->whereDoesntHave(
                'roles',
                fn (Builder $query) => $query->whereKey($superAdminRoleId)
            ))
            ->when($filters['company'] ?? [], fn (Builder $query, array $ids) => $query->whereIn('company_id', $ids))
            ->when($filters['role'] ?? [], fn (Builder $query, array $ids) => $query->whereHas(
                'roles',
                fn (Builder $query) => $query->whereKey($ids)
            ))
            ->when($isActive !== null && $isActive !== '', fn (Builder $query) => $query->where('is_active', (bool) $isActive))
            ->when($params['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', Apps::likePattern($search))
                    ->orWhere('email', 'like', Apps::likePattern($search))
                    ->orWhere('employee_id', 'like', Apps::likePattern($search))
            ))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderBy('id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, array $roleIds): User
    {
        return DB::transaction(function () use ($data, $roleIds): User {
            $data['password'] = filled($data['password'] ?? null) ? $data['password'] : Str::password(32);

            $this->releaseEmployeeId($data['employee_id'] ?? null, null);

            $user = User::onlyTrashed()->where('email', Str::lower(trim($data['email'])))->first();

            if ($user !== null) {
                $user->restore();
                $user->fill($data)->save();
            } else {
                $user = User::create($data);
            }

            $user->roles()->sync($roleIds);

            return $user;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(User $user, array $data, array $roleIds): User
    {
        return DB::transaction(function () use ($user, $data, $roleIds): User {
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $this->releaseEmployeeId($data['employee_id'] ?? null, $user->getKey());

            $user->update($data);
            $user->roles()->sync($roleIds);
            $user->flushAccessCache();

            return $user;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->roles()->detach();
            $user->delete();
        });
    }

    /**
     * Free an Employee ID still held by a deleted account so it can be reused.
     */
    protected function releaseEmployeeId(?string $employeeId, ?int $exceptUserId): void
    {
        if (blank($employeeId)) {
            return;
        }

        User::onlyTrashed()
            ->where('employee_id', trim($employeeId))
            ->when($exceptUserId !== null, fn (Builder $query) => $query->whereKeyNot($exceptUserId))
            ->update(['employee_id' => null]);
    }
}
