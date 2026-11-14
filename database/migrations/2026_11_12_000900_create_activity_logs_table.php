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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('url');
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->longText('data')->nullable()->comment('Redacted request payload (JSON)');
            $table->text('agent')->nullable();
            $table->string('ip', 45)->nullable();
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
        Schema::dropIfExists('activity_logs');
    }
};
