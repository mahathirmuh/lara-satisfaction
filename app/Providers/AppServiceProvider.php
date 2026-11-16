<?php

namespace App\Providers;

use App\Enums\PermissionAction;
use App\View\Composers\BreadcrumbComposer;
use App\View\Composers\NotificationComposer;
use App\View\Composers\SidebarComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
        $this->configureUrls();
        $this->registerSocialiteProviders();
        $this->registerBladeDirectives();

        View::composer('main.sidebar.menulist', SidebarComposer::class);
        View::composer('main.breadcrumb', BreadcrumbComposer::class);
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
     * Destructive command guard. There are no local passwords (SSO and the
     * network account only); network sign-in attempts are throttled by
     * App\Http\Requests\Auth\LoginRequest.
     */
    protected function configureSecurity(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    /**
     * In production every link, redirect and asset uses the public HTTPS
     * address (APP_URL, e.g. https://survey.merdekabattery.com), whatever
     * address the reverse proxy forwards to the container. Otherwise pages
     * served over HTTPS would load their scripts over plain HTTP.
     */
    protected function configureUrls(): void
    {
        $appUrl = (string) config('app.url');

        if ($this->app->isProduction() && str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
        }
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
