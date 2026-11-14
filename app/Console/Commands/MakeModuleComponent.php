<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Adds a class to an existing module:
 *
 *   php artisan make:module-component TrainingRecord controller Certificate
 *   php artisan make:module-component TrainingRecord repository Certificate
 *   php artisan make:module-component TrainingRecord request Certificate
 *   php artisan make:module-component TrainingRecord model Certificate
 *   php artisan make:module-component TrainingRecord middleware Certificate
 */
class MakeModuleComponent extends Command
{
    use GeneratesModuleFiles;

    /**
     * Component type => [stub, target path pattern].
     *
     * @var array<string, array<int, array{0: string, 1: string}>>
     */
    protected const COMPONENTS = [
        'controller' => [['component-controller.stub', 'Http/Controllers/{{ name }}Controller.php']],
        'repository' => [
            ['interface.stub', 'Repositories/{{ name }}Interface.php'],
            ['repository.stub', 'Repositories/{{ name }}Repository.php'],
        ],
        'request' => [['request.stub', 'Http/Requests/{{ name }}Request.php']],
        'model' => [['model.stub', 'Models/{{ name }}.php']],
        'middleware' => [['middleware.stub', 'Http/Middleware/{{ name }}Middleware.php']],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module-component
                            {module : Existing module name}
                            {type : controller, repository, request, model or middleware}
                            {name : Class base name in StudlyCase}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a controller, repository, request, model or middleware to a Merdeka module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = $this->studlyName((string) $this->argument('module'));
        $name = $this->studlyName((string) $this->argument('name'));
        $type = strtolower((string) $this->argument('type'));

        if ($module === null || $name === null) {
            $this->components->error('Module and class names must be valid StudlyCase class names.');

            return self::FAILURE;
        }

        if (! File::isDirectory($this->modulePath($module))) {
            $this->components->error("Module [{$module}] not found. Create it first with: php artisan make:module {$module}");

            return self::FAILURE;
        }

        if (! isset(self::COMPONENTS[$type])) {
            $this->components->error('Unknown type. Use one of: '.implode(', ', array_keys(self::COMPONENTS)).'.');

            return self::FAILURE;
        }

        $replacements = $this->moduleReplacements($module, $name);

        foreach (self::COMPONENTS[$type] as [$stub, $target]) {
            $this->writeStub($stub, $this->modulePath($module, strtr($target, $replacements)), $replacements, (bool) $this->option('force'));
        }

        return self::SUCCESS;
    }
}
