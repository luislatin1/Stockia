<?php

namespace App\Providers;

use App\Models\Module;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Cuando un paquete de módulo (ptv-pos, sales-quotes) está desactivado, su
 * propio Service Provider no registra rutas, por lo que navegar directo a su
 * URL da un 404 genérico. Este provider registra, solo para los módulos que
 * estén apagados, una ruta comodín que muestra un aviso explicando qué
 * módulo activar en vez de un 404 sin contexto.
 */
class ModuleGateServiceProvider extends ServiceProvider
{
    private const GATED_PREFIXES = [
        'ptv-pos' => ['ptv-pos'],
        'sales-quotes' => ['sales-quotes'],
        'sales' => ['comisiones', 'garantias'],
        'accounting' => ['contabilidad'],
        'inventory' => ['lotes', 'estantes'],
        'dte-sv-mh' => ['dte'],
    ];

    public function boot(): void
    {
        if (! Module::tableReady()) {
            return;
        }

        foreach (self::GATED_PREFIXES as $key => $prefixes) {
            if (Module::isEnabled($key)) {
                continue;
            }

            foreach ($prefixes as $prefix) {
                Route::middleware(['web', 'auth', 'company.selected'])
                    ->any($prefix.'/{any?}', fn () => view('modules.locked', ['key' => $key]))
                    ->where('any', '.*');
            }
        }
    }
}
