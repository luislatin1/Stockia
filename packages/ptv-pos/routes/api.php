<?php

use App\Http\Middleware\ResolveApiCompanyContext;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Stockia\PTVPos\Http\Controllers\Api\PTVPosApiController;

/*
|--------------------------------------------------------------------------
| API de ptv-pos (v1)
|--------------------------------------------------------------------------
|
| Mismo patrón que routes/api.php del core: auth:sanctum + StartSession
| explícito (no el grupo "web" completo, por el CSRF) + ResolveApiCompanyContext
| para resolver empresa/bodega desde headers. Gateado por el mismo
| Module::isEnabled('ptv-pos') que ya protege routes/web.php de este
| paquete (ver PTVPosServiceProvider::boot()).
|
*/

Route::prefix('api/v1/ptv-pos')
    ->name('api.v1.ptvpos.')
    ->middleware(['throttle:api', 'auth:sanctum', StartSession::class, ResolveApiCompanyContext::class, 'company.selected', 'can:ptvpos'])
    ->group(function () {
        Route::get('session', [PTVPosApiController::class, 'session'])->name('session.show');
        Route::post('session/open', [PTVPosApiController::class, 'openSession'])->name('session.open');
    });
