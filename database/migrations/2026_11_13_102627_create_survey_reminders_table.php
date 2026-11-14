<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deadline reminders already sent, so each reminder day of a survey
     * (e.g. 14 days before the end date) is only sent once.
     */
    public function up(): void
    {
        Schema::create('survey_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before')->comment('Reminder day, e.g. 14 = H-14');
            $table->unsignedInteger('recipients')->default(0);
            $table->timestamp('sent_at');

            $table->unique(['survey_id', 'days_before']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_reminders');
    }
};
