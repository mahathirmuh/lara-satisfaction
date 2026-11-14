<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers every module service provider found in modules/{Module}/Providers.
 *
 * Each module provider loads its own routes and views, so a new module only
 * needs to be created (php artisan make:module) and registered as a menu.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        foreach (glob(base_path('modules/*/Providers/*ServiceProvider.php')) ?: [] as $file) {
            $moduleName = basename(dirname($file, 2));
            $providerClass = 'Modules\\'.$moduleName.'\\Providers\\'.basename($file, '.php');

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
}
