<?php

namespace App\Services\Auth;

use App\Helpers\JobTitles;
use App\Models\UserManagement\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Accounts of people verified by single sign-on (Microsoft, Google) or the
 * network directory (LDAP). There are no local passwords: these are the only
 * ways in, and the account here only holds the profile and the roles.
 *
 * A person is matched on their company email. Someone without an account is
 * registered as an Employee on their first sign-in when automatic
 * registration is on (SSO_AUTO_REGISTER) and the email belongs to one of the
 * allowed domains (SSO_ALLOWED_DOMAINS). The job title and employee ID the
 * directory gives at sign-in are recorded; the job title decides the roles
 * (see JobTitleRoles).
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
     * Record the employee ID from the directory. An empty one keeps the ID
     * entered by hand. An ID held by a deleted account is freed for this
     * user, as when it is entered by hand; one held by another account is
     * left out (and logged) rather than failing the sign-in.
     */
    public function applyEmployeeId(User $user, ?string $employeeId): void
    {
        $employeeId = trim((string) $employeeId);

        if ($employeeId === '' || $employeeId === $user->employee_id) {
            return;
        }

        if (Str::length($employeeId) > User::EMPLOYEE_ID_MAX_LENGTH || preg_match(User::EMPLOYEE_ID_PATTERN, $employeeId) !== 1) {
            Log::warning('The employee ID from the directory is not a valid employee ID; it was not recorded.', ['user_id' => $user->id]);

            return;
        }

        $holder = User::withTrashed()->where('employee_id', $employeeId)->whereKeyNot($user->getKey())->first();

        if ($holder !== null && ! $holder->trashed()) {
            Log::warning('The employee ID from the directory already belongs to another account; it was not recorded.', [
                'user_id' => $user->id,
                'holder_id' => $holder->id,
            ]);

            return;
        }

        DB::transaction(function () use ($user, $holder, $employeeId): void {
            $holder?->forceFill(['employee_id' => null])->saveQuietly();
            $user->forceFill(['employee_id' => $employeeId])->saveQuietly();
        });
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
