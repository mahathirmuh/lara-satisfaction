<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A response stores WHAT was answered without any reference to the user.
     * It uses a random (v4) UUID and has no timestamps, so it cannot be matched
     * back to a participation by id order or submission time. A survey with
     * responses cannot be deleted (it is closed instead), which also leaves
     * its answers a single cascade path, as SQL Server requires.
     */
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('survey_id')->constrained()->noActionOnDelete();
            $table->foreignId('company_id')->nullable()->comment('Respondent company snapshot for segmented reporting')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
