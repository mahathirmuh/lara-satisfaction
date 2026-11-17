<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * How a reminder sent by hand went out: the notification in the
     * application, the e-mail, or both. The ones sent before went both ways.
     */
    public function up(): void
    {
        Schema::table('survey_manual_reminders', function (Blueprint $table) {
            $table->boolean('via_app')->default(true)->comment('Sent as a notification in the application');
            $table->boolean('via_email')->default(true)->comment('Sent by e-mail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_manual_reminders', function (Blueprint $table) {
            $table->dropColumn(['via_app', 'via_email']);
        });
    }
};
