<?php

use Illuminate\Support\Facades\Route;
use Stockia\Sales\Http\Controllers\CommissionController;
use Stockia\Sales\Http\Controllers\WarrantyController;

Route::middleware(['auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin'])->group(function () {
    Route::get('garantias', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('garantias/{warranty}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::post('garantias/{warranty}/reclamo', [WarrantyController::class, 'claim'])->name('warranties.claim');
    Route::post('garantias/{warranty}/resolver', [WarrantyController::class, 'resolve'])->name('warranties.resolve');

    Route::middleware(['role:Admin,SuperAdmin'])->group(function () {
        Route::get('comisiones', [CommissionController::class, 'index'])->name('commissions.index');
        Route::get('comisiones/{user}', [CommissionController::class, 'show'])->name('commissions.show');
        Route::post('comisiones/{commission}/pagar', [CommissionController::class, 'pay'])->name('commissions.pay');
    });
});
