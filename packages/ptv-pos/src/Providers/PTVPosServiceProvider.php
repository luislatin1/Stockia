<?php

namespace Stockia\PTVPos\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

class PTVPosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('ptv-pos')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ptvpos');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
