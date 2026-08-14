<?php

namespace Stockia\Inventory\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('inventory')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'inventory');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
