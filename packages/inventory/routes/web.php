<?php

use Illuminate\Support\Facades\Route;
use Stockia\Inventory\Http\Controllers\ProductBatchController;
use Stockia\Inventory\Http\Controllers\ProductLocationController;

Route::middleware(['auth', 'company.selected', 'role:Admin,SuperAdmin'])->group(function () {
    Route::middleware(['can:inventory-lotes'])->group(function () {
        Route::get('lotes', [ProductBatchController::class, 'index'])->name('product-batches.index');
        Route::get('lotes/create', [ProductBatchController::class, 'create'])->name('product-batches.create');
        Route::post('lotes', [ProductBatchController::class, 'store'])->name('product-batches.store');
        Route::delete('lotes/{lote}', [ProductBatchController::class, 'destroy'])->name('product-batches.destroy');
    });

    Route::middleware(['can:inventory-estantes'])->group(function () {
        Route::get('estantes', [ProductLocationController::class, 'index'])->name('product-locations.index');
        Route::get('estantes/create', [ProductLocationController::class, 'create'])->name('product-locations.create');
        Route::post('estantes', [ProductLocationController::class, 'store'])->name('product-locations.store');
        Route::delete('estantes/{estante}', [ProductLocationController::class, 'destroy'])->name('product-locations.destroy');
    });
});
