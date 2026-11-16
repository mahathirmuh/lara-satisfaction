<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The parent reference takes no delete action: SQL Server rejects a
     * self-referencing foreign key that cascades or sets null. Menu groups have
     * no route, and SQL Server allows only one null in a unique index, so there
     * the route is unique among the menus that have one (filtered index).
     */
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu')->noActionOnDelete();
            $table->string('title')->comment('Sidebar label');
            $table->string('route')->nullable()->comment('Named route of the menu landing page (*.index)');
            $table->string('icon', 100)->nullable()->comment('Tabler icon name without the ti- prefix');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('section', 100)->nullable()->comment('Sidebar section header for root menus');
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'sqlsrv') {
            DB::statement('create unique index menu_route_unique on menu (route) where route is not null');
        } else {
            Schema::table('menu', fn (Blueprint $table) => $table->unique('route'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
