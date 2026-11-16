<?php

namespace Modules\AuditLog\Providers;

use App\Providers\BaseModuleServiceProvider;

class AuditLogServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Folder name of the module, also used as its view namespace.
     */
    protected string $module = 'AuditLog';
}
