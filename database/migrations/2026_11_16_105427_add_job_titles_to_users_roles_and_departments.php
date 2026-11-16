<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Job title rules of the managers of the surveyed departments (by code).
     *
     * @var array<string, array<int, string>>
     */
    protected const DEPARTMENT_MANAGERS = [
        'SCM' => ['Supply Chain*Manager'],
        'OHS' => ['OHS Manager', 'Occupational Health & Safety Manager'],
        'ENV' => ['Environmental Manager', 'Environment Manager'],
        'EXT' => ['External Affair* Manager'],
        'FIN' => ['Finance Manager'],
        'INTF' => ['Interface Manager'],
    ];

    /**
     * Run the migrations.
     *
     * Roles follow the job title from the directory: users get their job
     * title, roles the job title rules that give them at sign-in and the
     * departments whose results they see, departments the job titles of their
     * managers.
     *
     * Existing installs move to IT Admin > Super Admin > Manager > Employee:
     * the unrestricted role (id 1) becomes IT Admin and the survey
     * administrators become Super Admin. RoleSeeder then adds the Manager role.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->comment('Job title from the directory, refreshed at every sign-in');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->json('job_titles')->nullable()->comment('Job title rules giving this role at sign-in (* any text, ! excludes)');
            $table->string('department_scope', 20)->default('ALL')->comment('Survey results of ALL departments or of those MANAGED by the job title');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->json('manager_job_titles')->nullable()->comment('Job title rules of the managers who see its survey results');
        });

        DB::table('roles')->where('id', 1)->where('name', 'Super Admin')->update(['name' => 'IT Admin']);
        DB::table('roles')->where('name', 'Survey Administrator')->update([
            'name' => 'Super Admin',
            'job_titles' => json_encode(['Support Services Deputy General Manager']),
        ]);

        foreach (self::DEPARTMENT_MANAGERS as $code => $jobTitles) {
            DB::table('departments')
                ->where('code', $code)
                ->whereNull('manager_job_titles')
                ->update(['manager_job_titles' => json_encode($jobTitles)]);
        }
    }

    /**
     * Reverse the migrations. The role names return; a Manager role created
     * by RoleSeeder stays (delete it first when it has no users).
     */
    public function down(): void
    {
        DB::table('roles')->where('id', '!=', 1)->where('name', 'Super Admin')->update(['name' => 'Survey Administrator']);
        DB::table('roles')->where('id', 1)->where('name', 'IT Admin')->update(['name' => 'Super Admin']);

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('manager_job_titles');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['job_titles', 'department_scope']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('job_title');
        });
    }
};
