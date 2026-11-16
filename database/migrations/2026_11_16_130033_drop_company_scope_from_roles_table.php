<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Roles no longer carry a company scope (Data Scope ALL / COMPANY): every
     * role covers every company. The department scope of survey results stays.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['func_role', 'setting_role']);
        });
    }

    /**
     * Reverse the migrations: the columns return with every role at ALL.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('func_role', 20)->default('ALL')->comment('Data scope: ALL or COMPANY');
            $table->json('setting_role')->nullable()->comment('Company ids covered by a COMPANY scoped role');
        });
    }
};
