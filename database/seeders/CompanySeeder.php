<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use Illuminate\Database\Seeder;

/**
 * The companies of the application: PT Merdeka Tsingshan Indonesia (MTI) and PT Merdeka Battery Materials (MBM)
 * (codes follow OrangeHR).
 */
class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            ['code' => 'MTI', 'name' => 'PT Merdeka Tsingshan Indonesia', 'short_name' => 'PTMTI', 'location' => 'IMIP, Morowali Regency, Central Sulawesi'],
            ['code' => 'MBM', 'name' => 'PT Merdeka Battery Materials', 'short_name' => 'PTMBM', 'location' => 'Jakarta'],
        ];

        foreach ($companies as $company) {
            Company::query()->updateOrCreate(['code' => $company['code']], [...$company, 'is_active' => true]);
        }
    }
}
