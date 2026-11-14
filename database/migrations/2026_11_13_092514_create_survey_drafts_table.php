<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Unfinished answers an employee saved to continue later. Unlike the
     * anonymous responses, a draft belongs to its user, so the answers are
     * stored encrypted, never shown to administrators and deleted as soon as
     * the survey is submitted (or once it is no longer open).
     */
    public function up(): void
    {
        Schema::create('survey_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('payload')->comment('Encrypted answers and current step');
            $table->timestamps();

            $table->unique(['survey_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_drafts');
    }
};
