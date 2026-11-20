<?php

namespace Stockia\DteSvMh\Providers;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;
use Stockia\DteSvMh\Console\DiagnosticoConexionCommand;
use Stockia\DteSvMh\Console\EsquemasCommand;
use Stockia\DteSvMh\Console\ProbarHistoricoCommand;
use Stockia\DteSvMh\Console\PruebasHomologacionCommand;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;

class DteSvMhServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DteSchemaRegistry::class);
    }

    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('dte-sv-mh')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dte');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([EsquemasCommand::class, ProbarHistoricoCommand::class, PruebasHomologacionCommand::class, DiagnosticoConexionCommand::class]);
        }
    }
}
