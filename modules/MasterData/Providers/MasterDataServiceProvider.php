<?php

namespace Modules\MasterData\Providers;

use App\Providers\BaseModuleServiceProvider;

class MasterDataServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Folder name of the module, also used as its view namespace.
     */
    protected string $module = 'MasterData';
}
