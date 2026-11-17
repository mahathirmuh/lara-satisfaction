<?php

namespace App\Services\Auth;

use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Roles that follow the job title from the directory. A role with job title
 * rules is given to everyone whose job title matches them; someone matching
 * none gets the default role (Employee). Roles without rules, such as IT
 * Admin, are given by hand and always kept. An IT Admin may also give a role
 * of the job title by hand (role_user.by_hand), e.g. Super Admin to someone
 * whose job title does not give it: it is kept whatever the job title.
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
        return array_values(array_unique(array_map('intval', [...$this->ruleRoles()->modelKeys(), $this->defaultRoleId()])));
    }

    /**
     * Give a user the roles chosen in User Management, then the roles of their
     * job title. Only an IT Admin gives roles of the job title by hand; from
     * anyone else they are left out, and those an IT Admin gave stay.
     *
     * @param  array<int, int|string>  $roleIds
     */
    public function assign(User $user, array $roleIds, bool $byItAdmin): void
    {
        $automaticRoleIds = $this->automaticRoleIds();
        $chosen = collect($roleIds)->map(fn ($id): int => (int) $id);

        if (! $byItAdmin) {
            $chosen = $chosen
                ->reject(fn (int $id): bool => in_array($id, $automaticRoleIds, true))
                ->merge(array_intersect($this->roleIdsByHand($user), $automaticRoleIds));
        }

        $user->roles()->sync($chosen->unique()->mapWithKeys(fn (int $id): array => [$id => ['by_hand' => true]])->all());
        $this->sync($user);
    }

    /**
     * Ids of the roles of a user given by hand: those without job title rules,
     * and those of the job title an IT Admin gave.
     *
     * @return array<int, int>
     */
    public function roleIdsByHand(User $user): array
    {
        $automaticRoleIds = $this->automaticRoleIds();

        return $user->roles()->get(['roles.id'])
            ->filter(fn (Role $role): bool => (bool) $role->pivot->by_hand || ! in_array((int) $role->id, $automaticRoleIds, true))
            ->map(fn (Role $role): int => (int) $role->id)
            ->values()
            ->all();
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
        $automaticRoleIds = array_map('intval', [...$ruleRoles->modelKeys(), $defaultRoleId]);

        /** @var array<int, array{by_hand: bool}> $roles */
        $roles = $user->roles()->get(['roles.id'])
            ->filter(fn (Role $role): bool => (bool) $role->pivot->by_hand || ! in_array((int) $role->id, $automaticRoleIds, true))
            ->mapWithKeys(fn (Role $role): array => [(int) $role->id => ['by_hand' => (bool) $role->pivot->by_hand]])
            ->all();

        $matchedRoleIds = $ruleRoles
            ->filter(fn (Role $role): bool => $role->matchesJobTitle($user->job_title))
            ->modelKeys();

        foreach ($matchedRoleIds ?: [$defaultRoleId] as $roleId) {
            $roles[(int) $roleId] ??= ['by_hand' => false];
        }

        $user->roles()->sync($roles);
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
