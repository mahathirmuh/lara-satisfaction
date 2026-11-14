<?php

namespace App\Providers;

use App\Enums\PermissionAction;
use App\View\Composers\NotificationComposer;
use App\View\Composers\SidebarComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Azure\Provider as AzureProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureModels();
        $this->configureSecurity();
        $this->registerSocialiteProviders();
        $this->registerBladeDirectives();

        View::composer('main.sidebar.menulist', SidebarComposer::class);
        View::composer('main.index', NotificationComposer::class);
    }

    /**
     * Surface Eloquent mistakes early outside production.
     */
    protected function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }

    /**
     * Password policy and destructive command guard. Login attempts are
     * throttled by App\Http\Requests\Auth\LoginRequest.
     */
    protected function configureSecurity(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());
    }

    /**
     * Register the Microsoft Azure (Entra ID) Socialite driver.
     */
    protected function registerSocialiteProviders(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('azure', AzureProvider::class);
        });
    }

    /**
     * Blade directive mirroring the CheckMenuPermission middleware so views can
     * hide actions the current user cannot perform. Usage in Blade:
     * "permission('user-management.user-registered', 'Create')" wrapped in the
     * usual at-sign directive / endpermission pair. The second argument is
     * Create, Read, Update or Delete and defaults to Read.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::if('permission', function (string $menuKey, string $ability = 'Read'): bool {
            $user = auth()->user();

            if ($user === null) {
                return false;
            }

            return $user->canAccessMenu($menuKey, PermissionAction::fromName($ability) ?? PermissionAction::Read);
        });
    }
}
