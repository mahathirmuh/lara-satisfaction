<?php

namespace App\Services\Auth;

use App\Helpers\JobTitles;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports the employees of the assessed departments from the network
 * directory, so they are listed, reminded and counted as survey respondents
 * before they ever sign in. Run daily (users:sync-directory) and from User
 * Management.
 *
 * - An enabled account with a company e-mail (SSO_ALLOWED_DOMAINS), an
 *   employee ID and a directory department of an assessed department
 *   (Department::directoryNameMap()) gets an account here, or has it
 *   refreshed: name, company, department, job title (and so its roles) and
 *   employee ID. New accounts get the default role, like at first sign-in.
 * - A disabled directory account deactivates the account here.
 * - Someone who moved to another department, or is no longer in the
 *   directory, keeps their account but stops being a respondent.
 * - An account deleted here is never brought back, and the activation an
 *   administrator set is left alone.
 */
class EmployeeDirectorySync
{
    /**
     * Cache key of the outcome of the last sync (shown in User Management).
     */
    public const LAST_RUN_CACHE = 'directory_sync.last_run';

    /**
     * Create a new service instance.
     */
    public function __construct(
        protected LdapAuthenticator $directory,
        protected CompanyAccounts $accounts,
    ) {}

    /**
     * Import the employees; with $dryRun only count what would change.
     *
     * @return array{employees: int, created: int, updated: int, unchanged: int, deactivated: int, left: int, skipped_deleted: int}
     *
     * @throws DirectoryUnavailableException
     */
    public function sync(bool $dryRun = false): array
    {
        $people = $this->directory->people();

        if ($people === []) {
            throw new DirectoryUnavailableException('The directory returned no people; nothing was changed.');
        }

        $departments = Department::directoryNameMap();
        $companyIds = $this->companyIds();
        $users = User::withTrashed()->get()->keyBy('email');
        $seen = [];
        $result = ['employees' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'deactivated' => 0, 'left' => 0, 'skipped_deleted' => 0];

        foreach ($people as $person) {
            if ($person->email === null) {
                continue;
            }

            $seen[$person->email] = true;
            $user = $users->get($person->email);
            $departmentId = $person->department === null
                ? null
                : ($departments[Department::normalizeDirectoryName($person->department)] ?? null);

            if (! $person->isEnabled) {
                if ($user !== null && ! $user->trashed() && $user->is_active) {
                    $dryRun || $user->forceFill(['is_active' => false])->saveQuietly();
                    $result['deactivated']++;
                }

                continue;
            }

            if ($departmentId === null || blank($person->employeeId) || ! $this->hasCompanyEmail($person->email)) {
                $this->leaveDepartment($user, $dryRun, $result);

                continue;
            }

            if ($user?->trashed()) {
                $result['skipped_deleted']++;

                continue;
            }

            $result['employees']++;
            $result[$this->import($person, $user, $departmentId, $this->companyIdFor($person->company, $companyIds), $dryRun)]++;
        }

        // People no longer in the directory keep their account but stop being respondents.
        foreach ($users as $user) {
            if (! isset($seen[$user->email]) && $user->directory_synced_at !== null) {
                $this->leaveDepartment($user, $dryRun, $result);
            }
        }

        if (! $dryRun) {
            Cache::forever(self::LAST_RUN_CACHE, ['at' => now()->toIso8601String(), 'result' => $result]);
        }

        return $result;
    }

    /**
     * Outcome of the last sync, if any.
     *
     * @return array{at: string, result: array<string, int>}|null
     */
    public static function lastRun(): ?array
    {
        return Cache::get(self::LAST_RUN_CACHE);
    }

    /**
     * Create or refresh the account of an employee; "created", "updated" or
     * "unchanged" (counted as updated only when something changed).
     */
    protected function import(DirectoryPerson $person, ?User $user, int $departmentId, ?int $companyId, bool $dryRun): string
    {
        $name = Str::limit(trim((string) $person->name) ?: Str::before((string) $person->email, '@'), 255, '');

        if ($user === null) {
            $dryRun || DB::transaction(function () use ($person, $name, $departmentId, $companyId): void {
                $user = User::create([
                    'name' => $name,
                    'email' => $person->email,
                    'password' => Str::password(32),
                    'is_active' => true,
                    'company_id' => $companyId,
                    'department_id' => $departmentId,
                ]);
                $user->roles()->attach((int) config('merdeka.default_role_id'));
                $this->refresh($user, $person);
            });

            return 'created';
        }

        $before = $user->only(['name', 'company_id', 'department_id', 'job_title', 'employee_id']);

        if ($dryRun) {
            $after = [
                'name' => $name,
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'job_title' => JobTitles::normalize($person->jobTitle) ?: null,
                'employee_id' => trim((string) $person->employeeId),
            ];

            return $after == $before ? 'unchanged' : 'updated';
        }

        DB::transaction(function () use ($user, $person, $name, $departmentId, $companyId): void {
            $user->forceFill(['name' => $name, 'company_id' => $companyId, 'department_id' => $departmentId])->saveQuietly();
            $this->refresh($user, $person);
        });

        return $user->only(array_keys($before)) === $before ? 'unchanged' : 'updated';
    }

    /**
     * Job title (and the roles that follow it), employee ID and sync time.
     */
    protected function refresh(User $user, DirectoryPerson $person): void
    {
        $this->accounts->applyJobTitle($user, $person->jobTitle);
        $this->accounts->applyEmployeeId($user, $person->employeeId);
        $user->forceFill(['directory_synced_at' => now()])->saveQuietly();
    }

    /**
     * The person no longer works in an assessed department.
     *
     * @param  array<string, int>  $result
     */
    protected function leaveDepartment(?User $user, bool $dryRun, array &$result): void
    {
        if ($user === null || $user->trashed() || $user->department_id === null) {
            return;
        }

        $dryRun || $user->forceFill(['department_id' => null])->saveQuietly();
        $result['left']++;
    }

    /**
     * Whether the e-mail belongs to an allowed company domain.
     */
    protected function hasCompanyEmail(string $email): bool
    {
        return in_array(Str::after($email, '@'), config('merdeka.sso.allowed_domains', []), true);
    }

    /**
     * Company ids by normalised name and code, plus the configured aliases
     * and the default company (key "").
     *
     * @return array<string, int>
     */
    protected function companyIds(): array
    {
        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->get(['id', 'code', 'name']);
        $byCode = $companies->mapWithKeys(fn (Company $company): array => [Str::upper($company->code) => (int) $company->id]);
        $ids = [];

        foreach ($companies as $company) {
            $ids[$this->normalizeCompany($company->name)] = (int) $company->id;
            $ids[$this->normalizeCompany($company->code)] = (int) $company->id;
        }

        foreach ((array) config('ldap.sync.company_aliases', []) as $alias => $code) {
            if ($byCode->has(Str::upper($code))) {
                $ids[$this->normalizeCompany((string) $alias)] = $byCode[Str::upper($code)];
            }
        }

        $default = $byCode[Str::upper((string) config('ldap.sync.default_company'))] ?? null;

        if ($default !== null) {
            $ids[''] = $default;
        }

        return $ids;
    }

    /**
     * Company of a directory company name, else the default company.
     *
     * @param  array<string, int>  $companyIds
     */
    protected function companyIdFor(?string $directoryCompany, array $companyIds): ?int
    {
        return $companyIds[$this->normalizeCompany((string) $directoryCompany)] ?? $companyIds[''] ?? null;
    }

    /**
     * "PT. Merdeka Tsingshan Indonesia" and "PT Merdeka Tsingshan Indonesia"
     * both become "merdeka tsingshan indonesia".
     */
    protected function normalizeCompany(string $name): string
    {
        $name = mb_strtolower(trim((string) preg_replace('/[.,]+/', ' ', $name)));
        $name = (string) preg_replace('/^pt\s+|\s+tbk$/', '', $name);

        return (string) preg_replace('/\s+/', ' ', trim($name));
    }
}
