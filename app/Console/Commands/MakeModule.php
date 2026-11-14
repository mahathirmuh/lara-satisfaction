<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Scaffolds a Merdeka module: provider, controller, request, middleware,
 * model, repository (interface + implementation), views and web routes.
 * The module is discovered automatically by App\Providers\ModuleServiceProvider.
 */
class MakeModule extends Command
{
    use GeneratesModuleFiles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module
                            {name? : Module name in StudlyCase, e.g. TrainingRecord}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Merdeka module (controller, repository, request, model, views, routes and provider)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = $this->studlyName((string) ($this->argument('name') ?? $this->ask('What is the module name?')));

        if ($module === null) {
            $this->components->error('The module name must be a valid StudlyCase class name.');

            return self::FAILURE;
        }

        if (File::isDirectory($this->modulePath($module)) && ! $this->option('force')) {
            $this->components->error("Module [{$module}] already exists. Use --force to overwrite its files.");

            return self::FAILURE;
        }

        $replacements = $this->moduleReplacements($module);
        $slug = Str::kebab($module);

        $files = [
            'provider.stub' => "Providers/{$module}ServiceProvider.php",
            'controller.stub' => "Http/Controllers/{$module}Controller.php",
            'request.stub' => "Http/Requests/{$module}Request.php",
            'middleware.stub' => "Http/Middleware/{$module}Middleware.php",
            'model.stub' => "Models/{$module}.php",
            'interface.stub' => "Repositories/{$module}Interface.php",
            'repository.stub' => "Repositories/{$module}Repository.php",
            'routes.stub' => "Routes/web/{$slug}.php",
            'view.stub' => 'Resources/views/index.blade.php',
        ];

        foreach ($files as $stub => $target) {
            $this->writeStub($stub, $this->modulePath($module, $target), $replacements, (bool) $this->option('force'));
        }

        File::ensureDirectoryExists($this->modulePath($module, 'Resources/js'));
        File::ensureDirectoryExists($this->modulePath($module, 'Resources/css'));

        $this->components->info("Module [{$module}] created in modules/{$module}.");
        $this->components->bulletList([
            "Register a menu with route \"{$slug}.index\" (database/seeders/MenuSeeder.php) and grant it in Role & Permission.",
            'Routes use the permission middleware: until the menu exists only the super admin can open them.',
            "Add a migration for the \"{$replacements['{{ table }}']}\" table if the module stores data.",
        ]);

        return self::SUCCESS;
    }
}
