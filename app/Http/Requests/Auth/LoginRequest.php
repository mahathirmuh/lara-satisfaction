<?php

namespace App\Http\Requests\Auth;

use App\Models\UserManagement\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Local sign in with an email address or Employee ID.
 */
class LoginRequest extends FormRequest
{
    /**
     * Failed attempts allowed per minute for a login + IP combination.
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
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Unknown accounts and wrong passwords share one message to avoid account
     * enumeration; the inactive message is only shown after a valid password.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            $this->loginField() => $this->normalizedLogin(),
            'password' => $this->string('password')->toString(),
        ];

        if (! Auth::guard('web')->validate($credentials)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->getLastAttempted();

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'Your account is inactive. Please contact the IT Helpdesk.',
            ]);
        }

        Auth::guard('web')->login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
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
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Column used to look the account up: email when the login contains "@".
     */
    public function loginField(): string
    {
        return str_contains($this->normalizedLogin(), '@') ? 'email' : 'employee_id';
    }

    /**
     * Trimmed login; emails are compared in lower case.
     */
    public function normalizedLogin(): string
    {
        $login = $this->string('login')->trim()->toString();

        return str_contains($login, '@') ? Str::lower($login) : $login;
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->normalizedLogin()).'|'.$this->ip());
    }
}
