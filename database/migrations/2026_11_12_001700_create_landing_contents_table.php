<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Editable content of the public landing page (single row).
     */
    public function up(): void
    {
        Schema::create('landing_contents', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title');
            $table->text('hero_intro')->comment('May contain {survey} for the title of the open survey');
            $table->string('hero_image_path')->nullable()->comment('Hero image on the public disk');
            $table->string('hero_image_alt')->nullable();
            $table->string('feedback_title');
            $table->text('feedback_body');
            $table->string('feedback_note')->nullable();
            $table->string('closing_title');
            $table->string('closing_note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_contents');
    }
};
