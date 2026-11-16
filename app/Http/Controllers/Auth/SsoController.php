<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Alert;
use App\Http\Controllers\Controller;
use App\Services\Auth\CompanyAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Single sign-on with Google (Merdeka Copper Gold) and Microsoft Entra ID
 * (Merdeka Battery Materials). With the network account (LDAP) these are the
 * only ways in; there are no local passwords.
 */
class SsoController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected CompanyAccounts $accounts) {}

    /**
     * Socialite driver per provider slug used in the URL.
     */
    public const DRIVERS = [
        'microsoft' => 'azure',
        'google' => 'google',
    ];

    /**
     * Company SSO: its button is always on the login page, even before the
     * app registration is configured.
     */
    public const COMPANY_PROVIDER = 'microsoft';

    /**
     * Provider slugs whose client id and secret are configured.
     *
     * @return array<int, string>
     */
    public static function enabledProviders(): array
    {
        return array_values(array_filter(
            array_keys(self::DRIVERS),
            fn (string $provider): bool => filled(config('services.'.self::DRIVERS[$provider].'.client_id'))
                && filled(config('services.'.self::DRIVERS[$provider].'.client_secret')),
        ));
    }

    /**
     * SSO buttons of the login page: the company provider always, the other
     * providers once configured.
     *
     * @return array<string, bool> Provider slug => whether it is configured
     */
    public static function loginProviders(): array
    {
        $enabled = self::enabledProviders();

        return collect(array_keys(self::DRIVERS))
            ->filter(fn (string $provider): bool => $provider === self::COMPANY_PROVIDER || in_array($provider, $enabled, true))
            ->mapWithKeys(fn (string $provider): array => [$provider => in_array($provider, $enabled, true)])
            ->all();
    }

    /**
     * Redirect to the identity provider.
     */
    public function redirect(string $provider): SymfonyRedirectResponse
    {
        if ($notConfigured = $this->redirectIfNotConfigured($provider)) {
            return $notConfigured;
        }

        return Socialite::driver(self::DRIVERS[$provider])->redirect();
    }

    /**
     * Handle the identity provider callback.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if ($notConfigured = $this->redirectIfNotConfigured($provider)) {
            return $notConfigured;
        }

        try {
            $socialUser = Socialite::driver(self::DRIVERS[$provider])->user();
        } catch (Throwable $exception) {
            report($exception);
            Alert::error('Sign in failed', 'We could not verify your '.Str::headline($provider).' account. Please try again.');

            return redirect()->route('login');
        }

        $email = $this->emailOf($socialUser);

        if ($email === null) {
            Alert::warning('Sign in failed', 'Your '.Str::headline($provider).' account did not share an email address.');

            return redirect()->route('login');
        }

        $domain = Str::after($email, '@');

        if (in_array($domain, config('merdeka.sso.blocked_domains', []), true)) {
            Alert::warning('Public email is not allowed', "Please sign in with your company account. Email used: {$email}");

            return redirect()->route('login');
        }

        $user = $this->accounts->resolve($email, $socialUser->getName());

        if ($user === null) {
            Alert::warning('Account not registered', "The account {$email} is not registered in this application. Please contact HR or the IT Helpdesk.");

            return redirect()->route('login');
        }

        if (($jobTitle = $this->jobTitleOf($socialUser)) !== null) {
            $this->accounts->applyJobTitle($user, $jobTitle);
        }

        $this->accounts->applyEmployeeId($user, $this->rawProfileValue($socialUser, 'employeeId'));

        if (! $user->is_active) {
            Alert::warning('Account inactive', 'Your account has been deactivated. Please contact the IT Helpdesk.');

            return redirect()->route('login');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Alert::success('Welcome!', 'Signed in as '.$user->name.'.');

        return redirect()->intended($user->homeUrl());
    }

    /**
     * Back to the login page with an explanation when the provider has no
     * app registration yet.
     */
    protected function redirectIfNotConfigured(string $provider): ?RedirectResponse
    {
        if (in_array($provider, self::enabledProviders(), true)) {
            return null;
        }

        Alert::warning(
            Str::headline($provider).' sign-in is not active yet',
            'Single sign-on with '.Str::headline($provider).' will be available soon. Please sign in with your network account (username and password) for now.',
        );

        return redirect()->route('login');
    }

    /**
     * Lower-cased email of the provider account (Microsoft: mail, else UPN).
     */
    protected function emailOf(SocialiteUser $socialUser): ?string
    {
        $raw = method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : [];
        $email = Str::lower(trim((string) (($raw['mail'] ?? null) ?: $socialUser->getEmail())));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Job title of a Microsoft account (Graph jobTitle). Only a filled value
     * is used: Entra may lack the titles the network directory has, and an
     * empty one must not take roles away. Google shares no job title.
     */
    protected function jobTitleOf(SocialiteUser $socialUser): ?string
    {
        return $this->rawProfileValue($socialUser, 'jobTitle');
    }

    /**
     * A filled value of the provider profile (Microsoft Graph user fields
     * such as jobTitle or employeeId); null when missing or empty.
     */
    protected function rawProfileValue(SocialiteUser $socialUser, string $field): ?string
    {
        $raw = method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : [];
        $value = trim((string) ($raw[$field] ?? ''));

        return $value !== '' ? $value : null;
    }
}
