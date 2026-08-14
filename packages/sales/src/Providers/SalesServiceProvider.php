<?php

namespace Stockia\Sales\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

class SalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('sales')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'sales');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
