<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The assessed department a person works in (from the directory), which
     * makes them a survey respondent, and when the directory sync last found
     * them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('company_id')
                ->comment('Own (assessed) department from the directory: a survey respondent')
                ->constrained()->nullOnDelete();
            $table->timestamp('directory_synced_at')->nullable()->comment('Last time the directory sync found this person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('directory_synced_at');
        });
    }
};
