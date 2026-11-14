<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Renders the stubs in app/Console/Commands/stubs/module into a module folder.
 */
trait GeneratesModuleFiles
{
    /**
     * Placeholders shared by every module stub.
     *
     * @return array<string, string>
     */
    protected function moduleReplacements(string $module, ?string $name = null): array
    {
        $name ??= $module;

        return [
            '{{ module }}' => $module,
            '{{ name }}' => $name,
            '{{ slug }}' => Str::kebab($module),
            '{{ title }}' => Str::headline($module),
            '{{ variable }}' => Str::camel($name),
            '{{ table }}' => Str::snake(Str::pluralStudly($name)),
        ];
    }

    /**
     * Write a rendered stub, refusing to overwrite unless forced.
     *
     * @param  array<string, string>  $replacements
     */
    protected function writeStub(string $stub, string $target, array $replacements, bool $force = false): bool
    {
        if (File::exists($target) && ! $force) {
            $this->components->warn('Skipped existing file: '.$this->relativePath($target));

            return false;
        }

        File::ensureDirectoryExists(dirname($target));
        File::put($target, strtr(File::get(app_path('Console/Commands/stubs/module/'.$stub)), $replacements));

        $this->components->twoColumnDetail($this->relativePath($target), '<fg=green;options=bold>CREATED</>');

        return true;
    }

    /**
     * Absolute path inside a module.
     */
    protected function modulePath(string $module, string $path = ''): string
    {
        return base_path('modules/'.$module.($path !== '' ? '/'.$path : ''));
    }

    /**
     * Normalised StudlyCase name; null when it is not a valid class name.
     */
    protected function studlyName(string $value): ?string
    {
        $studly = Str::studly(trim($value));

        return preg_match('/^[A-Z][A-Za-z0-9]*$/', $studly) === 1 ? $studly : null;
    }

    /**
     * Path relative to the project root for console output.
     */
    protected function relativePath(string $path): string
    {
        return ltrim(str_replace([base_path(), '\\'], ['', '/'], $path), '/');
    }
}
