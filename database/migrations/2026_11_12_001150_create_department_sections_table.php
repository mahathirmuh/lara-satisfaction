<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sections (service units) of a department, e.g. External Affairs:
     * Asset Protection. Employees choose the section they evaluate and see
     * its overview (team picture, work summary, role overview) first.
     */
    public function up(): void
    {
        Schema::create('department_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable()->comment('Role overview shown before the evaluation');
            $table->string('team_photo_path')->nullable()->comment('Team picture on the public disk');
            $table->string('team_caption')->nullable();
            $table->string('work_summary_path')->nullable()->comment('Work summary image on the public disk');
            $table->string('work_summary_caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_sections');
    }
};
