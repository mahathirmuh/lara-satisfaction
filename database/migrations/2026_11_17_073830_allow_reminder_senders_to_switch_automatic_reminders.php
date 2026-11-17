<?php

use App\Enums\PermissionAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Turning the automatic reminders on or off is an Update on the Reminders
     * menu: every role that may send reminders by hand there (Create, e.g.
     * Super Admin) may switch them as well. RoleSeeder grants the same on a
     * new install, where the menu does not exist yet at this point.
     */
    public function up(): void
    {
        $menuId = DB::table('menu')->where('route', 'survey-management.reminder.index')->value('id');

        if ($menuId === null) {
            return;
        }

        $senderRoleIds = DB::table('menu_role_permission')
            ->where('menu_id', $menuId)
            ->where('permission_id', PermissionAction::Create->value)
            ->pluck('role_id');

        foreach ($senderRoleIds as $roleId) {
            $grant = ['menu_id' => $menuId, 'role_id' => $roleId, 'permission_id' => PermissionAction::Update->value];

            if (! DB::table('menu_role_permission')->where($grant)->exists()) {
                DB::table('menu_role_permission')->insert($grant + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    /**
     * Reverse the migrations. The Update permissions stay: they may have been
     * given by hand in Role & Permission as well.
     */
    public function down(): void
    {
        //
    }
};
