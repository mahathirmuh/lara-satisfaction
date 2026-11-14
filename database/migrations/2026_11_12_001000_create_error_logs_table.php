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
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->string('exception')->nullable()->comment('Exception class name');
            $table->text('message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->text('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->longText('data')->nullable()->comment('Redacted request payload (JSON)');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
