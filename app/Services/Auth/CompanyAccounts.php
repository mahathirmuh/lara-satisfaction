<?php

namespace App\Services\Auth;

use App\Helpers\JobTitles;
use App\Models\UserManagement\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Accounts of people verified by single sign-on (Microsoft, Google) or the
 * network directory (LDAP). There are no local passwords: these are the only
 * ways in, and the account here only holds the profile and the roles.
 *
 * A person is matched on their company email. Someone without an account is
 * registered as an Employee on their first sign-in when automatic
 * registration is on (SSO_AUTO_REGISTER) and the email belongs to one of the
 * allowed domains (SSO_ALLOWED_DOMAINS). The job title the directory gives at
 * sign-in is recorded and decides the roles (see JobTitleRoles).
 */
class CompanyAccounts
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected JobTitleRoles $roles) {}

    /**
     * Record the job title from the directory and refresh the roles that
     * follow it.
     */
    public function applyJobTitle(User $user, ?string $jobTitle): void
    {
        $jobTitle = JobTitles::normalize($jobTitle) ?: null;

        if ($user->job_title !== $jobTitle) {
            $user->forceFill(['job_title' => $jobTitle])->saveQuietly();
        }

        $this->roles->sync($user);
    }

    /**
     * The account of a verified person, registered when allowed; null when
     * there is none.
     */
    public function resolve(string $email, ?string $name): ?User
    {
        $email = Str::lower(trim($email));

        return User::query()->where('email', $email)->first() ?? $this->register($email, $name);
    }

    /**
     * Register an unknown person as an Employee when automatic registration
     * allows it. A deleted account is never brought back this way.
     */
    protected function register(string $email, ?string $name): ?User
    {
        $domain = Str::after($email, '@');

        if (! config('merdeka.sso.auto_register') || ! in_array($domain, config('merdeka.sso.allowed_domains', []), true)) {
            return null;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($email, $name): User {
            $user = User::create([
                'name' => Str::limit(trim((string) $name) ?: Str::before($email, '@'), 255, ''),
                'email' => $email,
                'password' => Str::password(32),
                'is_active' => true,
            ]);

            $user->roles()->attach((int) config('merdeka.default_role_id'));

            return $user;
        });
    }
}
