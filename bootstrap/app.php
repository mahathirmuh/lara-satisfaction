<?php

use App\Helpers\LogApps;
use App\Http\Middleware\CheckMenuPermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogActivityMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
            EnsureUserIsActive::class,
            LogActivityMiddleware::class,
        ]);

        $middleware->alias([
            'permission' => CheckMenuPermission::class,
            'role' => RoleMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(fn (Request $request): string => $request->user()?->homeUrl() ?? route('dashboard.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            if (config('merdeka.log.errors')) {
                $isHttpContext = ! app()->runningInConsole() || app()->runningUnitTests();

                LogApps::errorLog($exception, $isHttpContext ? request() : null);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
