<?php

namespace Modules\Dashboard\Providers;

use App\Providers\BaseModuleServiceProvider;

class DashboardServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Folder name of the module, also used as its view namespace.
     */
    protected string $module = 'Dashboard';
}
