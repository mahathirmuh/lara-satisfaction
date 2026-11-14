<?php

namespace Tests\Feature\Auth;

use App\Helpers\LogApps;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Log\LogActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_always_offers_microsoft_and_marks_it_coming_soon_until_configured(): void
    {
        config(['services.azure.client_id' => null, 'services.azure.client_secret' => null]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in with Merdeka Account')
            ->assertSeeInOrder(['Sign In with SSO', route('sso.redirect', 'microsoft'), 'Coming soon'])
            ->assertDontSee(route('sso.redirect', 'google'));
    }

    public function test_configured_providers_are_offered_without_the_coming_soon_label(): void
    {
        config([
            'services.azure.client_id' => 'azure-client-id',
            'services.azure.client_secret' => 'azure-client-secret',
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('sso.redirect', 'microsoft'))
            ->assertSee(route('sso.redirect', 'google'))
            ->assertDontSee('Coming soon');
    }

    public function test_employee_signs_in_with_email_in_any_letter_case_and_lands_on_my_survey(): void
    {
        $employee = $this->employee(['email' => 'budi@merdekabattery.com']);

        $this->post(route('login.store'), [
            'login' => ' BUDI@MerdekaBattery.com ',
            'password' => 'password',
        ])->assertRedirect(route('survey-form.index'));

        $this->assertAuthenticatedAs($employee);
        $this->assertNotNull($employee->fresh()->last_login_at);
    }

    public function test_user_signs_in_with_employee_id(): void
    {
        $admin = $this->surveyAdmin(['employee_id' => 'MBM-0042']);

        $this->post(route('login.store'), [
            'login' => 'MBM-0042',
            'password' => 'password',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_and_unknown_account_get_the_same_message(): void
    {
        $this->employee(['email' => 'budi@merdekabattery.com']);

        $this->from(route('login'))
            ->post(route('login.store'), ['login' => 'budi@merdekabattery.com', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);

        $this->post(route('login.store'), ['login' => 'nobody@merdekabattery.com', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_requires_both_fields(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['login', 'password']);

        $this->assertGuest();
    }

    public function test_inactive_user_with_the_correct_password_is_told_the_account_is_inactive(): void
    {
        $this->employee(['email' => 'left@merdekabattery.com', 'is_active' => false]);

        $this->post(route('login.store'), ['login' => 'left@merdekabattery.com', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'Your account is inactive. Please contact the IT Helpdesk.']);

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_too_many_failed_attempts(): void
    {
        $this->employee(['email' => 'budi@merdekabattery.com']);

        foreach (range(1, LoginRequest::MAX_ATTEMPTS) as $attempt) {
            $this->post(route('login.store'), ['login' => 'budi@merdekabattery.com', 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['login' => 'budi@merdekabattery.com', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertStringContainsString('Too many login attempts', session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_user_can_sign_out(): void
    {
        $this->actingAs($this->employee())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_signed_in_user_visiting_the_login_page_is_sent_home(): void
    {
        $this->actingAs($this->employee())
            ->get(route('login'))
            ->assertRedirect(route('survey-form.index'));
    }

    public function test_password_is_redacted_in_the_activity_log(): void
    {
        $this->employee(['email' => 'budi@merdekabattery.com']);

        $this->post(route('login.store'), ['login' => 'budi@merdekabattery.com', 'password' => 'Secret-Value-123']);

        $log = LogActivity::query()->where('route_name', 'login.store')->sole();

        $this->assertStringNotContainsString('Secret-Value-123', $log->data);
        $this->assertSame(LogApps::REDACTED, json_decode($log->data, true)['password']);
    }
}
