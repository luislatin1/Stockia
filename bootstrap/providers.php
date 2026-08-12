<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Inventory\Providers\InventoryServiceProvider::class,
    App\Modules\Sales\Providers\SalesServiceProvider::class,
    App\Modules\Accounting\Providers\AccountingServiceProvider::class,
    App\Providers\ModuleGateServiceProvider::class,
];
