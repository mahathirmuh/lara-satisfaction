<?php

namespace Modules\SystemLog\Providers;

use App\Providers\BaseModuleServiceProvider;

class SystemLogServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Folder name of the module, also used as its view namespace.
     */
    protected string $module = 'SystemLog';
}
