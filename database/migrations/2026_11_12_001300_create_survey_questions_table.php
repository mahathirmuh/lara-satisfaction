<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->comment('Null means a general (cross-department) question')
                ->constrained()->restrictOnDelete();
            $table->foreignId('department_section_id')->nullable()->comment('Null: asked about the section the employee chooses')
                ->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('rating')->comment('rating (service quality 1-5), criticality (1-5) or text');
            $table->text('question');
            $table->string('help_text', 500)->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['survey_id', 'department_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
