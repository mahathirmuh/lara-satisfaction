<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The employee photo (thumbnail on the private disk, from the access card
     * system) and the card update time it was taken from, to refresh it when
     * the card photo changes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('locale')->comment('Photo thumbnail on the private disk');
            $table->timestamp('photo_updated_at')->nullable()->comment('Card record update time of the stored photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_updated_at']);
        });
    }
};
