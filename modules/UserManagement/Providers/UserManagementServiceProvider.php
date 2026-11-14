<?php

namespace Modules\UserManagement\Providers;

use App\Providers\BaseModuleServiceProvider;

class UserManagementServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Folder name of the module, also used as its view namespace.
     */
    protected string $module = 'UserManagement';
}
