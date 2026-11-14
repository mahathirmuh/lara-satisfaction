<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Sidebar menu entry. A menu with a route owns every route sharing its
 * "menu key" (route name without the trailing action), which is how the
 * permission middleware maps requests to the RBAC matrix.
 */
#[Table('menu')]
#[Fillable(['parent_id', 'title', 'route', 'icon', 'sort_order', 'section'])]
class Menu extends Model
{
    public const ROUTE_KEY_CACHE = 'rbac_menu_route_key_map';

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ROUTE_KEY_CACHE));
        static::deleted(fn () => Cache::forget(self::ROUTE_KEY_CACHE));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menu_role')->withTimestamps();
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * @return HasMany<Menu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * A group is only shown when it is granted and at least one of its
     * children is visible, so revoking the group hides the whole branch.
     *
     * @param  array<int, int>  $allowedMenuIds
     */
    public function isVisibleFor(array $allowedMenuIds): bool
    {
        if (! in_array($this->id, $allowedMenuIds, true)) {
            return false;
        }

        if ($this->children->isEmpty()) {
            return true;
        }

        return $this->children->contains(fn (Menu $child): bool => $child->isVisibleFor($allowedMenuIds));
    }

    /**
     * Link of the menu, or null when it has no (registered) route.
     */
    public function url(): ?string
    {
        return $this->route && Route::has($this->route) ? route($this->route) : null;
    }

    /**
     * Menu key of a route name: "user-management.user-registered.store" => "user-management.user-registered".
     */
    public static function keyForRoute(string $routeName): string
    {
        return Str::beforeLast($routeName, '.');
    }

    /**
     * Menu id owning a menu key, or null when no menu is registered for it.
     */
    public static function idForKey(string $menuKey): ?int
    {
        return static::routeKeyMap()[$menuKey] ?? null;
    }

    /**
     * Menu id owning a route name, or null when no menu is registered for it.
     */
    public static function idForRoute(string $routeName): ?int
    {
        return static::idForKey(static::keyForRoute($routeName));
    }

    /**
     * Map of menu key => menu id for every menu that carries a route. The
     * structure rarely changes, so it is cached and flushed on menu changes;
     * the per-role grants themselves are never cached across requests.
     *
     * @return array<string, int>
     */
    public static function routeKeyMap(): array
    {
        return Cache::remember(self::ROUTE_KEY_CACHE, (int) config('merdeka.rbac_cache_ttl', 3600), function (): array {
            return static::query()
                ->whereNotNull('route')
                ->get(['id', 'route'])
                ->mapWithKeys(fn (Menu $menu): array => [static::keyForRoute($menu->route) => (int) $menu->id])
                ->all();
        });
    }
}
