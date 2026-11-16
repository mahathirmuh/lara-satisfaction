<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Behind the reverse proxy of https://survey.merdekabattery.com the pages
 * must link to the public HTTPS address, or browsers block their scripts.
 */
class ProductionUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_links_use_the_public_https_address(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://survey.merdekabattery.com']);

        $provider = $this->app->getProvider(AppServiceProvider::class);
        (fn () => $this->configureUrls())->call($provider);

        $this->assertSame('https://survey.merdekabattery.com/login', route('login'));
        $this->assertSame('https://survey.merdekabattery.com/assets/js/apps.min.js', asset('assets/js/apps.min.js'));
    }

    public function test_forwarded_https_is_only_believed_from_a_trusted_proxy(): void
    {
        $forwarded = [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'survey.merdekabattery.com',
            'X-Forwarded-Port' => '443',
        ];

        $this->withHeaders($forwarded)
            ->get('/login')
            ->assertOk()
            ->assertDontSee('https://survey.merdekabattery.com/assets/', false);

        config(['trustedproxy.proxies' => '*']);

        $this->withHeaders($forwarded)
            ->get('/login')
            ->assertOk()
            ->assertSee('https://survey.merdekabattery.com/assets/', false);
    }
}
