<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Department names used in the company directory (the AD "department"
     * attribute) for the employees of each assessed department, as found in
     * the mbma.com directory on 2026-11-16.
     *
     * @var array<string, array<int, string>>
     */
    private const DIRECTORY_NAMES = [
        'SCM' => ['Supply Chain Management'],
        'OHS' => ['Occupational Health and Safety'],
        'ENV' => ['Environment'],
        'EXT' => ['External Affair'],
        'FIN' => ['Finance'],
        'INTF' => ['Interface'],
        'HR' => ['Human Resources'],
    ];

    /**
     * Which directory departments belong to each assessed department: their
     * employees are imported from the directory as the survey respondents.
     * Existing departments get the names used in the directory today.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->json('directory_names')->nullable()->after('manager_job_titles')
                ->comment('Directory (AD department attribute) names of the department employees');
        });

        foreach (self::DIRECTORY_NAMES as $code => $names) {
            DB::table('departments')
                ->where('code', $code)
                ->whereNull('directory_names')
                ->update(['directory_names' => json_encode($names)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('directory_names');
        });
    }
};
