<?php

namespace App\Models\UserManagement;

use App\Enums\RoleScope;
use Database\Factories\UserManagement\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role with menu access (menu_role), CRUD permissions per menu
 * (menu_role_permission) and a data scope (func_role + setting_role).
 */
#[Fillable(['name', 'func_role', 'setting_role', 'home_page'])]
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
     * Whether this is the built-in super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->getKey() === (int) config('merdeka.super_admin_role_id');
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
