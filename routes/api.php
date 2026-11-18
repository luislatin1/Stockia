<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SaleController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Autenticación por token (Laravel Sanctum, personal access tokens — no
| cookies, por lo que no hace falta CSRF aquí). Cada request debe declarar
| con qué empresa/bodega quiere trabajar vía los headers X-Company-Id y
| X-Warehouse-Id (ResolveApiCompanyContext, alias "api.company"): se
| resuelven una vez por request y se dejan en session() para que el resto
| del código existente (CompanyScope, currentCompany(), accessors de stock,
| Gate::define de permisos) funcione exactamente igual que en la web, sin
| necesitar un refactor de esas piezas. Por eso el grupo protegido incluye
| StartSession explícitamente (no el grupo "web" completo: ese trae
| VerifyCsrfToken, que rompería peticiones autenticadas solo por token).
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api')->name('login');

    Route::middleware(['throttle:api', 'auth:sanctum', StartSession::class])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::middleware(['api.company', 'company.selected'])->group(function () {
            Route::middleware(['can:productos'])->group(function () {
                Route::get('products', [ProductController::class, 'index'])->name('products.index');
                Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
                Route::post('products', [ProductController::class, 'store'])->name('products.store');
                Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            });

            Route::middleware(['role:Vendedor,Admin,SuperAdmin', 'can:ventas'])->group(function () {
                Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
                Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
                Route::post('sales', [SaleController::class, 'store'])
                    ->middleware('dte.document')
                    ->name('sales.store');
            });
        });
    });
});
