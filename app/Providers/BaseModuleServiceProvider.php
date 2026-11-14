<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Base provider of a Merdeka module (modules/{Module}).
 *
 * - binds every Repositories/{Name}Interface to Repositories/{Name}Repository
 * - loads the views under the "{Module}::" namespace
 * - loads every route file in Routes/web
 */
abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * Folder name of the module, also used as its view namespace.
     */
    protected string $module;

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach (glob($this->modulePath('Repositories/*Interface.php')) ?: [] as $file) {
            $name = basename($file, 'Interface.php');
            $interface = "Modules\\{$this->module}\\Repositories\\{$name}Interface";
            $repository = "Modules\\{$this->module}\\Repositories\\{$name}Repository";

            if (interface_exists($interface) && class_exists($repository)) {
                $this->app->bind($interface, $repository);
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('Resources/views'), $this->module);

        foreach (glob($this->modulePath('Routes/web/*.php')) ?: [] as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }
    }

    /**
     * Absolute path inside the module folder.
     */
    protected function modulePath(string $path = ''): string
    {
        return base_path('modules/'.$this->module.($path !== '' ? '/'.$path : ''));
    }
}
