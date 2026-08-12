<?php

namespace App\Modules\Inventory\Providers;

use App\Models\Module;
use App\Modules\Core\Providers\ModuleServiceProvider;

class InventoryServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('inventory')) {
            return;
        }

        parent::boot();
    }

    protected function routes(): array
    {
        return [
            'web' => [
                base_path('app/Modules/Inventory/Routes/web.php'),
            ],
        ];
    }
}

