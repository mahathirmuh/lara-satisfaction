<?php

namespace App\Models\UserManagement;

use App\Enums\PermissionAction;
use App\Enums\RoleScope;
use App\Models\Organization\Company;
use App\Models\Survey\SurveyParticipation;
use Database\Factories\UserManagement\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Application user (employee or administrator).
 *
 * Access control follows the Merdeka RBAC matrix: a role is granted menus
 * (menu_role) and CRUD permissions per menu (menu_role_permission).
 */
#[Fillable(['name', 'email', 'employee_id', 'company_id', 'password', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Menu ids granted to the user's roles, cached for this instance.
     *
     * @var array<int, int>|null
     */
    protected ?array $grantedMenuIds = null;

    /**
     * Granted permission ids keyed by menu id, cached for this instance.
     *
     * @var array<int, array<int, int>>|null
     */
    protected ?array $grantedMenuPermissions = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Emails are stored trimmed and in lower case so lookups are case-insensitive on every driver.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(trim($value)),
        );
    }

    /**
     * Employee IDs are stored trimmed; empty values become null.
     */
    protected function employeeId(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value) ? trim($value) : null,
        );
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Surveys the user has completed (identity only, never the answers).
     *
     * @return HasMany<SurveyParticipation, $this>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(SurveyParticipation::class);
    }

    /**
     * Only users allowed to sign in.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Whether the user holds the super admin role (bypasses RBAC).
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('id', (int) config('merdeka.super_admin_role_id'));
    }

    /**
     * The primary role of the user (lowest id).
     */
    public function getRole(): ?Role
    {
        return $this->roles->sortBy('id')->first();
    }

    /**
     * Whether any of the user's roles carries one of the given names.
     */
    public function hasAnyRole(string ...$roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * Ids of the menus granted to the user's roles.
     *
     * @return array<int, int>
     */
    public function grantedMenuIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Menu::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        return $this->grantedMenuIds ??= MenuRole::query()
            ->whereIn('role_id', $this->roles->modelKeys())
            ->distinct()
            ->pluck('menu_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * ACCESS check: whether one of the user's roles is granted the menu.
     */
    public function guardRole(int $menuId): bool
    {
        return $this->isSuperAdmin() || in_array($menuId, $this->grantedMenuIds(), true);
    }

    /**
     * CRUD check: whether one of the user's roles holds the permission on the menu.
     */
    public function hasMenuPermission(int $menuId, PermissionAction|int $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissionId = $permission instanceof PermissionAction ? $permission->value : $permission;

        $this->grantedMenuPermissions ??= MenuRolePermission::query()
            ->whereIn('role_id', $this->roles->modelKeys())
            ->get(['menu_id', 'permission_id'])
            ->groupBy('menu_id')
            ->map(fn ($rows): array => $rows->pluck('permission_id')->map(fn ($id): int => (int) $id)->unique()->values()->all())
            ->all();

        return in_array($permissionId, $this->grantedMenuPermissions[$menuId] ?? [], true);
    }

    /**
     * Whether the user may perform an action on a menu identified by its key
     * (the menu route without its trailing action, e.g. "survey-report").
     */
    public function canAccessMenu(string $menuKey, PermissionAction $action = PermissionAction::Read): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $menuId = Menu::idForKey($menuKey);

        if ($menuId === null) {
            return false;
        }

        return $this->guardRole($menuId) && $this->hasMenuPermission($menuId, $action);
    }

    /**
     * CRUD abilities of the user on a menu, e.g. for showing row actions.
     *
     * @return array{create: bool, read: bool, update: bool, delete: bool}
     */
    public function menuAbilities(string $menuKey): array
    {
        $abilities = [];

        foreach (PermissionAction::cases() as $action) {
            $abilities[strtolower($action->name)] = $this->canAccessMenu($menuKey, $action);
        }

        return $abilities;
    }

    /**
     * Company ids whose data the user may see; null means every company.
     *
     * @return array<int, int>|null
     */
    public function scopedCompanyIds(): ?array
    {
        $companyIds = [];

        foreach ($this->roles as $role) {
            if ($role->func_role === RoleScope::All) {
                return null;
            }

            $companyIds = [...$companyIds, ...$role->companyIds()];
        }

        return array_values(array_unique($companyIds));
    }

    /**
     * Landing page after sign in, based on the primary role's home menu.
     */
    public function homeUrl(): string
    {
        $homeMenu = $this->getRole()?->homeMenu;

        if ($homeMenu?->route && Route::has($homeMenu->route) && $this->guardRole($homeMenu->id)) {
            return route($homeMenu->route);
        }

        return route('dashboard.index');
    }

    /**
     * Forget cached grants, e.g. after the user's roles changed.
     */
    public function flushAccessCache(): void
    {
        $this->grantedMenuIds = null;
        $this->grantedMenuPermissions = null;
        $this->unsetRelation('roles');
    }
}
