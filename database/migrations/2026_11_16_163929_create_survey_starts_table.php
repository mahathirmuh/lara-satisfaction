<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a person first opened a survey's form, so "My Survey" and Survey
     * Progress show it as In Progress until it is completed, even without a
     * saved draft. Holds no answers.
     */
    public function up(): void
    {
        Schema::create('survey_starts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');

            $table->unique(['survey_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_starts');
    }
};
