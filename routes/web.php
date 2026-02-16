<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanySelectionController;
use App\Http\Controllers\WarehouseSelectionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\WarehouseController;


/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/select-company', [CompanySelectionController::class, 'index'])
    ->name('company.select');

    Route::post('/select-company', [CompanySelectionController::class, 'store']);

    Route::get('/select-warehouse', [WarehouseSelectionController::class, 'index'])
    ->name('warehouse.select');

    Route::post('/select-warehouse', [WarehouseSelectionController::class, 'store']);

    Route::middleware(['company.selected'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])
            ->name('inventory_movements.index');

        Route::middleware(['role:Admin,SuperAdmin'])->group(function () {
            Route::get('products/{product}/adjust', [ProductController::class, 'adjust'])
                ->name('products.adjust');

            Route::post('products/{product}/adjust', [ProductController::class, 'processAdjustment'])
                ->name('products.processAdjustment');

            Route::resource('products', ProductController::class)
                ->only(['index','create','store', 'show', 'edit', 'update', 'destroy']);

            Route::resource('users', UserController::class)
                ->only(['index','create','store','show', 'edit', 'update', 'destroy']);
            Route::patch('users/{user}/role', [UserController::class, 'updateRole'])
                ->name('users.role.update');
            Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
            Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
            Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
            Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        });

        Route::middleware(['role:Vendedor,Admin,SuperAdmin'])->group(function () {
            Route::resource('sales', SaleController::class)
                ->only(['index','create','store', 'show', 'edit', 'update', 'destroy']);
            Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])
                ->name('sales.cancel');
            Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])
                ->name('sales.ticket');
            Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])
                ->name('sales.invoice');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Catálogos
|--------------------------------------------------------------------------
*/

Route::view('/currencies', 'currencies.index')->name('currencies.index');
Route::view('/categories', 'categories.index')->name('categories.index');

/*
|--------------------------------------------------------------------------
| Inventario
|--------------------------------------------------------------------------
*/

Route::view('/inventory', 'inventory.index')->name('inventory.index');

/*
|--------------------------------------------------------------------------
| Otros
|--------------------------------------------------------------------------
*/

Route::view('/sale-items', 'sale_items.index')->name('sale_items.index');

require __DIR__.'/auth.php';
