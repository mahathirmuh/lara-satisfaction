<?php

namespace Database\Seeders;

use App\Enums\PermissionAction;
use App\Models\UserManagement\Permission;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * CRUD permissions. Their ids must match App\Enums\PermissionAction.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PermissionAction::cases() as $action) {
            $permission = Permission::query()->firstOrCreate(['name' => $action->name]);

            if ((int) $permission->id !== $action->value) {
                throw new RuntimeException("Permission [{$action->name}] must have id {$action->value} (got {$permission->id}). Seed an empty permissions table.");
            }
        }
    }
}
