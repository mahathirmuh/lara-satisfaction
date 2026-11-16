<?php

namespace Tests\Feature\Auth;

use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.azure.client_id' => 'azure-client-id',
            'services.azure.client_secret' => 'azure-client-secret',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, string|null>, 2: string}>
     */
    public static function unconfiguredProviders(): array
    {
        return [
            'google without credentials' => ['google', [], 'Google sign-in is not active yet'],
            'microsoft without credentials' => ['microsoft', ['services.azure.client_id' => null], 'Microsoft sign-in is not active yet'],
            'microsoft without client secret' => ['microsoft', ['services.azure.client_secret' => null], 'Microsoft sign-in is not active yet'],
        ];
    }

    /**
     * @param  array<string, string|null>  $config
     */
    #[DataProvider('unconfiguredProviders')]
    public function test_provider_without_app_registration_explains_that_it_is_not_active_yet(string $provider, array $config, string $title): void
    {
        config($config);

        $this->get(route('sso.redirect', $provider))
            ->assertRedirect(route('login'))
            ->assertSessionHas('alert.title', $title);

        $this->get(route('sso.callback', $provider))
            ->assertRedirect(route('login'))
            ->assertSessionHas('alert.title', $title);

        $this->assertGuest();
    }

    public function test_redirect_sends_the_user_to_microsoft(): void
    {
        $this->get(route('sso.redirect', 'microsoft'))
            ->assertRedirectContains('login.microsoftonline.com');
    }

    public function test_registered_user_is_signed_in_from_the_microsoft_callback(): void
    {
        $employee = $this->employee(['email' => 'budi@merdekabattery.com']);
        $this->fakeMicrosoftUser('Budi@MerdekaBattery.com');

        $this->get(route('sso.callback', 'microsoft'))->assertRedirect(route('survey-form.index'));

        $this->assertAuthenticatedAs($employee);
    }

    public function test_public_mailbox_accounts_are_rejected(): void
    {
        $this->employee(['email' => 'budi@gmail.com']);
        $this->fakeMicrosoftUser('budi@gmail.com');

        $this->get(route('sso.callback', 'microsoft'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('alert.title', 'Public email is not allowed');

        $this->assertGuest();
    }

    public function test_unregistered_user_is_rejected_when_auto_registration_is_disabled(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => false]);
        $this->fakeMicrosoftUser('new.hire@merdekabattery.com');

        $this->get(route('sso.callback', 'microsoft'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('alert.title', 'Account not registered');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'new.hire@merdekabattery.com']);
    }

    public function test_unregistered_user_of_an_allowed_domain_is_registered_as_employee_when_enabled(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => true]);
        $this->fakeMicrosoftUser('new.hire@merdekabattery.com', 'New Hire');

        $this->get(route('sso.callback', 'microsoft'))->assertRedirect(route('survey-form.index'));

        $user = User::query()->where('email', 'new.hire@merdekabattery.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('New Hire', $user->name);
        $this->assertSame(['Employee'], $user->roles->pluck('name')->all());
    }

    public function test_auto_registration_ignores_domains_outside_the_allow_list(): void
    {
        $this->seedAccessControl();
        config(['merdeka.sso.auto_register' => true]);
        $this->fakeMicrosoftUser('someone@contractor.co.id');

        $this->get(route('sso.callback', 'microsoft'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'someone@contractor.co.id']);
    }

    public function test_microsoft_job_title_decides_the_roles(): void
    {
        $employee = $this->employee(['email' => 'budi@merdekabattery.com']);
        $this->fakeMicrosoftUser('budi@merdekabattery.com', jobTitle: 'OHS Manager');

        $this->get(route('sso.callback', 'microsoft'))->assertRedirect();

        $employee->refresh();
        $this->assertSame('OHS Manager', $employee->job_title);
        $this->assertSame(['Manager'], $employee->roles->pluck('name')->all());
    }

    public function test_microsoft_account_without_a_job_title_keeps_the_known_one(): void
    {
        $manager = $this->manager(['email' => 'budi@merdekabattery.com', 'job_title' => 'OHS Manager']);
        $this->fakeMicrosoftUser('budi@merdekabattery.com');

        $this->get(route('sso.callback', 'microsoft'))->assertRedirect();

        $manager->refresh();
        $this->assertSame('OHS Manager', $manager->job_title);
        $this->assertSame(['Manager'], $manager->roles->pluck('name')->all());
    }

    public function test_inactive_user_is_not_signed_in(): void
    {
        $this->employee(['email' => 'left@merdekabattery.com', 'is_active' => false]);
        $this->fakeMicrosoftUser('left@merdekabattery.com');

        $this->get(route('sso.callback', 'microsoft'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('alert.title', 'Account inactive');

        $this->assertGuest();
    }

    /**
     * Make the Microsoft provider return the given account.
     */
    protected function fakeMicrosoftUser(string $email, string $name = 'Budi Santoso', ?string $jobTitle = null): void
    {
        $socialUser = (new SocialiteUser)->setRaw(['mail' => $email, 'jobTitle' => $jobTitle])->map([
            'id' => 'azure-object-id',
            'name' => $name,
            'email' => $email,
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('azure')->andReturn($provider);
    }
}
