<?php

namespace Stockia\Accounting\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('accounting')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'accounting');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
