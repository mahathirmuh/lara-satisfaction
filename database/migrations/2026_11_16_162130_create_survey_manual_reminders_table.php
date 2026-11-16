<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reminders sent by hand from Survey Management > Reminders, next to the
     * scheduled ones (survey_reminders): who sent them, to whom (everyone
     * who has not completed the survey, or chosen people) and how many
     * people were reminded. Users are only soft deleted, so the sender keeps
     * no action on delete (SQL Server forbids a second cascade path).
     */
    public function up(): void
    {
        Schema::create('survey_manual_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->string('audience', 10)->comment('all = everyone who has not completed the survey, custom = chosen people');
            $table->unsignedInteger('recipients')->default(0)->comment('People reminded');
            $table->unsignedInteger('skipped')->default(0)->comment('Chosen people left out (completed, inactive or not taking surveys)');
            $table->timestamp('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_manual_reminders');
    }
};
