<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use Illuminate\Database\Seeder;

/**
 * Active companies of the Merdeka group (codes follow OrangeHR).
 */
class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            ['code' => 'MCG', 'name' => 'PT Merdeka Copper Gold', 'short_name' => 'PTMDKA', 'location' => 'Jakarta'],
            ['code' => 'MBM', 'name' => 'PT Merdeka Battery Materials', 'short_name' => 'PTMBM', 'location' => 'Jakarta'],
            ['code' => 'MTI', 'name' => 'PT Merdeka Tsingshan Indonesia', 'short_name' => 'PTMTI', 'location' => 'IMIP, Morowali Regency, Central Sulawesi'],
            ['code' => 'SCM', 'name' => 'PT Sulawesi Cahaya Mineral', 'short_name' => 'PTSCM', 'location' => 'Konawe Regency, Southeast Sulawesi'],
            ['code' => 'MMS', 'name' => 'PT Merdeka Mining Servis', 'short_name' => 'PTMMS', 'location' => 'Morowali'],
            ['code' => 'IKIP', 'name' => 'PT Indonesia Konawe Industrial Park', 'short_name' => 'PTIKIP', 'location' => 'IKIP'],
            ['code' => 'BSI', 'name' => 'PT Bumi Suksesindo', 'short_name' => 'PTBSI', 'location' => 'Banyuwangi'],
            ['code' => 'BTR', 'name' => 'PT Batutua Tembaga Raya', 'short_name' => 'PTBTR', 'location' => 'Wetar'],
            ['code' => 'PETS', 'name' => 'PT Puncak Emas Tani Sejahtera', 'short_name' => 'PTPETS', 'location' => 'Pohuwato Regency, Gorontalo'],
        ];

        foreach ($companies as $company) {
            Company::query()->updateOrCreate(['code' => $company['code']], [...$company, 'is_active' => true]);
        }
    }
}
