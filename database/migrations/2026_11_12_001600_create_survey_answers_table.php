<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Answers use random (v4) UUIDs as well, so their insertion order is not
     * observable through sequential ids. A department question is answered
     * once for every section of the department (department_section_id);
     * department and section are stored for fast aggregation.
     */
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_section_id')->nullable()->comment('Section the employee evaluated')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable()->comment('1-5 for rating questions');
            $table->text('comment')->nullable()->comment('Free text for text questions');

            $table->unique(['survey_response_id', 'survey_question_id', 'department_section_id'], 'survey_answers_response_question_section_unique');
            $table->index(['survey_question_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
