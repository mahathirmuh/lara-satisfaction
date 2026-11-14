<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * CRUD permission (Create, Read, Update, Delete). Ids mirror App\Enums\PermissionAction.
 */
#[Fillable(['name'])]
class Permission extends Model
{
    //
}
