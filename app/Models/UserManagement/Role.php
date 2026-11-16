<?php

namespace App\Models\UserManagement;

use App\Enums\DepartmentScope;
use App\Enums\RoleScope;
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
 * (menu_role_permission), a company scope (func_role + setting_role) and a
 * department scope for survey results. A role with job title rules is given
 * automatically at sign-in to the people whose job title matches them.
 */
#[Fillable(['name', 'func_role', 'setting_role', 'home_page', 'job_titles', 'department_scope'])]
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
            'func_role' => RoleScope::class,
            'setting_role' => 'array',
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
     * Company ids covered by a COMPANY scoped role.
     *
     * @return array<int, int>
     */
    public function companyIds(): array
    {
        if ($this->func_role !== RoleScope::Company) {
            return [];
        }

        return array_map('intval', $this->setting_role ?? []);
    }
}
