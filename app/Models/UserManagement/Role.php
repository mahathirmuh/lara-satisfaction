<?php

namespace App\Models\UserManagement;

use App\Enums\DepartmentScope;
use App\Enums\PermissionAction;
use App\Helpers\JobTitles;
use Database\Factories\UserManagement\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role with menu access (menu_role), CRUD permissions per menu
 * (menu_role_permission) and a department scope for survey results. A role
 * with job title rules is given automatically at sign-in to the people whose
 * job title matches them.
 */
#[Fillable(['name', 'home_page', 'job_titles', 'department_scope'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'home_page' => 'integer',
            'job_titles' => 'array',
            'department_scope' => DepartmentScope::class,
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Menu, $this>
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_role')->withTimestamps();
    }

    /**
     * @return HasMany<MenuRolePermission, $this>
     */
    public function menuPermissions(): HasMany
    {
        return $this->hasMany(MenuRolePermission::class);
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function homeMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'home_page');
    }

    /**
     * Whether this is the built-in IT Admin role (unrestricted, only given manually).
     */
    public function isItAdmin(): bool
    {
        return $this->getKey() === (int) config('merdeka.it_admin_role_id');
    }

    /**
     * Whether the role is given by job title rules (never the IT Admin role).
     */
    public function isGivenByJobTitle(): bool
    {
        return ! $this->isItAdmin() && filled($this->job_titles);
    }

    /**
     * Whether a job title earns this role.
     */
    public function matchesJobTitle(?string $jobTitle): bool
    {
        return $this->isGivenByJobTitle() && JobTitles::matches($this->job_titles, $jobTitle);
    }

    /**
     * The role as the audit trail records it: its form, and what it may do on each menu it is given ("Menu Survey
     * Report" => "Read"; a group without permissions of its own is "Access only").
     *
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        $permissions = $this->menuPermissions()->get(['menu_id', 'permission_id'])->groupBy('menu_id');
        $values = [
            'Name' => $this->name,
            'Home page' => $this->homeMenu()->value('title'),
            'Job titles' => implode(', ', $this->job_titles ?? []),
            'Results of' => $this->department_scope?->name,
        ];

        foreach ($this->menus()->orderBy('title')->get(['menu.id', 'menu.title']) as $menu) {
            $actions = collect(PermissionAction::cases())
                ->filter(fn (PermissionAction $action): bool => ($permissions[$menu->id] ?? collect())->contains('permission_id', $action->value))
                ->map(fn (PermissionAction $action): string => $action->name);

            $values['Menu '.$menu->title] = $actions->isEmpty() ? 'Access only' : $actions->implode(', ');
        }

        return $values;
    }
}
