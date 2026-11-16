<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use App\Models\UserManagement\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The first super admin: the company email of a real person (SEED_ADMIN_EMAIL),
 * who signs in with Microsoft SSO or their network account (LDAP) as there are
 * no local passwords. Running it again grants the role to that email's
 * account, e.g. after its owner signed in once and was registered as Employee.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = Str::lower(trim((string) config('merdeka.seed.admin_email')));

        if ($email === '') {
            $this->command?->warn('No super admin seeded: set SEED_ADMIN_EMAIL to the company email (Microsoft / network account) of the first administrator.');

            return;
        }

        $admin = User::withTrashed()->where('email', $email)->first();

        if ($admin?->trashed()) {
            $this->command?->warn("Super admin [{$email}] is a deleted account: restore it in User Management or set another SEED_ADMIN_EMAIL.");

            return;
        }

        $admin ??= User::create([
            'name' => Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title()->toString(),
            'email' => $email,
            'password' => Str::password(32),
            'company_id' => Company::query()->where('code', 'MBM')->value('id'),
            'is_active' => true,
        ]);

        $admin->roles()->syncWithoutDetaching([(int) config('merdeka.super_admin_role_id')]);

        $this->command?->info("Super admin: {$email} (signs in with Microsoft SSO or the network account that has this email).");
    }
}
