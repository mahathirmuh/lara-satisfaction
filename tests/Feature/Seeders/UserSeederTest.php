<?php

namespace Tests\Feature\Seeders;

use App\Models\UserManagement\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
    }

    public function test_first_super_admin_is_the_configured_company_email_without_a_password(): void
    {
        config(['merdeka.seed.admin_email' => ' Budi.Santoso@MerdekaBattery.com ']);

        $this->seed(UserSeeder::class);

        $admin = User::query()->where('email', 'budi.santoso@merdekabattery.com')->sole();
        $this->assertSame('Budi Santoso', $admin->name);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertFalse(Hash::check('', $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_existing_account_of_the_configured_email_becomes_super_admin(): void
    {
        $employee = $this->employee(['email' => 'budi.santoso@merdekabattery.com']);
        config(['merdeka.seed.admin_email' => 'budi.santoso@merdekabattery.com']);

        $this->seed(UserSeeder::class);

        $this->assertSame(['Super Admin', 'Employee'], $employee->fresh()->roles->sortBy('id')->pluck('name')->values()->all());
        $this->assertSame(1, User::query()->where('email', 'budi.santoso@merdekabattery.com')->count());
    }

    public function test_nobody_is_seeded_without_an_admin_email(): void
    {
        config(['merdeka.seed.admin_email' => null]);

        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }
}
