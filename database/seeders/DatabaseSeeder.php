<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            CompanySeeder::class,
            MenuSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            DepartmentSeeder::class,
            SurveySeeder::class,
        ]);

        if (config('merdeka.seed.demo_data') && ! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
