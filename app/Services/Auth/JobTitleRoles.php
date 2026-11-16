<?php

namespace App\Services\Auth;

use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Roles that follow the job title from the directory. A role with job title
 * rules is given to everyone whose job title matches them; someone matching
 * none gets the default role (Employee). Roles without rules, such as IT
 * Admin, are given by hand and always kept.
 */
class JobTitleRoles
{
    /**
     * Ids of the roles decided by the job title (rule roles and the default role).
     *
     * @return array<int, int>
     */
    public function automaticRoleIds(): array
    {
        return array_values(array_unique([...$this->ruleRoles()->modelKeys(), $this->defaultRoleId()]));
    }

    /**
     * Refresh the job title roles of a user, keeping the roles given by hand.
     */
    public function sync(User $user): void
    {
        $this->syncWith($user, $this->ruleRoles());
    }

    /**
     * Refresh every user, e.g. after the job title rules of a role changed.
     */
    public function syncAll(): void
    {
        $ruleRoles = $this->ruleRoles();

        User::query()->chunkById(200, function (Collection $users) use ($ruleRoles): void {
            foreach ($users as $user) {
                $this->syncWith($user, $ruleRoles);
            }
        });
    }

    /**
     * Give the matching rule roles (or the default role) and keep the others.
     *
     * @param  Collection<int, Role>  $ruleRoles
     */
    protected function syncWith(User $user, Collection $ruleRoles): void
    {
        $defaultRoleId = $this->defaultRoleId();
        $automaticRoleIds = [...$ruleRoles->modelKeys(), $defaultRoleId];

        $manualRoleIds = $user->roles()->pluck('roles.id')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => in_array($id, $automaticRoleIds, true))
            ->all();

        $matchedRoleIds = $ruleRoles
            ->filter(fn (Role $role): bool => $role->matchesJobTitle($user->job_title))
            ->modelKeys();

        $user->roles()->sync(array_values(array_unique([...$manualRoleIds, ...($matchedRoleIds ?: [$defaultRoleId])])));
        $user->flushAccessCache();
    }

    /**
     * Roles given by job title rules.
     *
     * @return Collection<int, Role>
     */
    protected function ruleRoles(): Collection
    {
        return Role::query()
            ->whereNotNull('job_titles')
            ->get()
            ->filter(fn (Role $role): bool => $role->isGivenByJobTitle())
            ->values();
    }

    /**
     * The role of people whose job title matches no rule.
     */
    protected function defaultRoleId(): int
    {
        return (int) config('merdeka.default_role_id');
    }
}
