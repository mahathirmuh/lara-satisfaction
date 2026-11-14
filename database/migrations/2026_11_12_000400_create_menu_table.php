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
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu')->nullOnDelete();
            $table->string('title')->comment('Sidebar label');
            $table->string('route')->nullable()->unique()->comment('Named route of the menu landing page (*.index)');
            $table->string('icon', 100)->nullable()->comment('Tabler icon name without the ti- prefix');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('section', 100)->nullable()->comment('Sidebar section header for root menus');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
