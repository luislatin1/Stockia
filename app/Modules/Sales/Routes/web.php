<?php

use App\Modules\Sales\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin'])->group(function () {
    Route::resource('sales', SaleController::class)
        ->only(['index','create','store', 'show', 'edit', 'update', 'destroy']);

    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])
        ->name('sales.cancel');

    Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])
        ->name('sales.ticket');

    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])
        ->name('sales.invoice');
});

