<?php

namespace App\Http\Requests\Auth;

use App\Services\Auth\CompanyAccounts;
use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\LdapAuthenticator;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign in with the network account (LDAP / Active Directory). There is no
 * local password: the directory checks it, and the account here is found by
 * the person's company email (see CompanyAccounts).
 */
class LoginRequest extends FormRequest
{
    /**
     * Failed attempts allowed per minute for a username + IP combination.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * A wrong password, an unknown or disabled directory account share one
     * message. A directory that cannot answer is reported as an outage and is
     * not counted as a failed attempt.
     *
     * @throws ValidationException
     */
    public function authenticate(LdapAuthenticator $directory, CompanyAccounts $accounts): void
    {
        $this->ensureIsNotRateLimited();

        if (! $directory->isConfigured()) {
            throw ValidationException::withMessages([
                'username' => __('Network sign-in is not configured yet. Please sign in with Microsoft or contact the IT Helpdesk.'),
            ]);
        }

        try {
            $identity = $directory->authenticate($this->directoryUsername(), $this->string('password')->toString());
        } catch (DirectoryUnavailableException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'username' => __('The network sign-in service cannot be reached right now. Please try again shortly.'),
            ]);
        }

        if ($identity === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = $identity->email !== null ? $accounts->resolve($identity->email, $identity->name) : null;

        if ($user === null) {
            throw ValidationException::withMessages([
                'username' => __('Your network account is not registered in this application. Please contact HR or the IT Helpdesk.'),
            ]);
        }

        $accounts->applyJobTitle($user, $identity->jobTitle);
        $accounts->applyEmployeeId($user, $identity->employeeId);
        $accounts->applyDepartment($user, $identity->department);

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => __('Your account is inactive. Please contact the IT Helpdesk.'),
            ]);
        }

        Auth::guard('web')->login($user, $this->boolean('remember'));
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * The directory username: what was typed, without an "@domain" part, so
     * "budi.santoso@merdekabattery.com" signs in as "budi.santoso".
     */
    public function directoryUsername(): string
    {
        return Str::before($this->string('username')->trim()->toString(), '@');
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->directoryUsername()).'|'.$this->ip());
    }
}
