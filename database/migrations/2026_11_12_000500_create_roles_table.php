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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Role name (Super Admin, Survey Administrator, Employee, ...)');
            $table->string('func_role', 20)->default('ALL')->comment('Data scope: ALL or COMPANY');
            $table->json('setting_role')->nullable()->comment('Company ids covered by a COMPANY scoped role');
            $table->foreignId('home_page')->nullable()->constrained('menu')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
