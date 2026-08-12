<?php

namespace Stockia\SalesQuotes\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

class SalesQuotesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('sales-quotes')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'salesquotes');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
