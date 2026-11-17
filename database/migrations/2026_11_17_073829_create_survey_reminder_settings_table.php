<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The switch of the automatic deadline reminders (Survey Management >
     * Reminders), a single row. Without a row they are on, as before.
     */
    public function up(): void
    {
        Schema::create('survey_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('automatic')->default(true)->comment('Whether the daily survey:send-deadline-reminders sends anything');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_reminder_settings');
    }
};
