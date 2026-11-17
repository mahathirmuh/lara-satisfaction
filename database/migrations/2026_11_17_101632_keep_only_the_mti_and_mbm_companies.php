<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Companies kept: PT Merdeka Tsingshan Indonesia and PT Merdeka Battery Materials.
     *
     * @var array<int, string>
     */
    protected const KEPT = ['MTI', 'MBM'];

    /**
     * Run the migrations.
     *
     * Only MTI and MBM stay: the other companies of the group go (on lara-kpi
     * no account and no response belonged to them). A user or a response of
     * one of them would keep its row with no company (nullOnDelete).
     */
    public function up(): void
    {
        DB::table('companies')->whereNotIn('code', self::KEPT)->delete();
    }

    /**
     * Reverse the migrations. The companies are not restored: CompanySeeder of
     * an earlier version lists them.
     */
    public function down(): void
    {
        //
    }
};
