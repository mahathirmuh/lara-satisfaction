<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Three roles stay: IT Admin > Super Admin > Employee. The Manager role
     * goes, with its job title rules and grants (role_user, menu_role and
     * menu_role_permission cascade); whoever is left without a role gets the
     * default one (Employee, config merdeka.default_role_id), as the job title
     * rules give it when none matches.
     */
    public function up(): void
    {
        $managerId = DB::table('roles')->where('name', 'Manager')->value('id');

        if ($managerId === null) {
            return;
        }

        $holders = DB::table('role_user')->where('role_id', $managerId)->pluck('user_id');
        DB::table('roles')->where('id', $managerId)->delete();

        $defaultRoleId = (int) config('merdeka.default_role_id');
        $withoutRole = $holders->reject(fn ($userId): bool => DB::table('role_user')->where('user_id', $userId)->exists());

        foreach ($withoutRole as $userId) {
            DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $defaultRoleId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations. The role is not restored: RoleSeeder of an
     * earlier version makes it again, and the job titles give it back at sign-in.
     */
    public function down(): void
    {
        //
    }
};
