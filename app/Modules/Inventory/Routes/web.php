<?php

use App\Modules\Inventory\Http\Controllers\InventoryMovementController;
use App\Modules\Inventory\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'company.selected'])->group(function () {
    Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])
        ->name('inventory_movements.index');

    Route::middleware(['role:Admin,SuperAdmin'])->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    });
});

