<?php

namespace Stockia\DteSvMh\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

class DteSvMhServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('dte-sv-mh')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dte');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
