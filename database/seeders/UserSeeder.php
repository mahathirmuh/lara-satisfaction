<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use App\Models\UserManagement\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Initial super admin account. No password is hard-coded: it comes from
 * SEED_ADMIN_PASSWORD or is generated and printed once.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = (string) config('merdeka.seed.admin_email');

        if (User::withTrashed()->where('email', Str::lower($email))->exists()) {
            $this->command?->info("Super admin [{$email}] already exists; password unchanged.");

            return;
        }

        $password = (string) (config('merdeka.seed.admin_password') ?: Str::password(16, symbols: false));

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => $password,
            'company_id' => Company::query()->where('code', 'MBM')->value('id'),
            'is_active' => true,
        ]);

        $admin->roles()->sync([(int) config('merdeka.super_admin_role_id')]);

        $this->command?->warn('Super admin account created:');
        $this->command?->line("  Email    : {$email}");
        $this->command?->line(config('merdeka.seed.admin_password') ? '  Password : (from SEED_ADMIN_PASSWORD)' : "  Password : {$password}");
        $this->command?->line('  Change it after the first sign in (Account > Change Password).');
    }
}
