<?php
namespace TuEmpresa\SvDte;

use Illuminate\Support\ServiceProvider;

class DteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dte.php', 'dte');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/dte.php' => config_path('dte.php'),
        ], 'dte-config');
    }
}