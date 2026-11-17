<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a role was given to a person by hand in User Management. A role given by hand stays whatever the job title,
 * also a role that otherwise follows the job title (e.g. Super Admin given by an IT Admin): see JobTitleRoles.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->boolean('by_hand')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropColumn('by_hand');
        });
    }
};
