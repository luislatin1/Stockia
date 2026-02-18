<?php

namespace Stockia\PTVPos\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class PTVPosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Schema::hasTable('modules')) {
            $module = DB::table('modules')->where('key', 'ptv-pos')->first();

            if ($module && ! (bool) $module->enabled) {
                return;
            }
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ptvpos');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}

