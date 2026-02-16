<?php

use App\Models\Company;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanySelectionController;
use App\Http\Controllers\WarehouseSelectionController;
use App\Http\Controllers\RoleController;


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

    // Route::get('/dashboard', function () {
    //     $products = \App\Models\Product::where('company_id', session('company_id'))->get();
    //     return view('dashboard', compact('products'));
    // })->name('dashboard');

    Route::get('products/{product}/adjust', [ProductController::class, 'adjust'])
    ->name('products.adjust');

    Route::post('products/{product}/adjust', [ProductController::class, 'processAdjustment'])
    ->name('products.processAdjustment');

    Route::resource('products', ProductController::class)
        ->only(['index','create','store', 'show', 'edit', 'update', 'destroy']);

    Route::resource('sales', SaleController::class)
        ->only(['index','create','store', 'show', 'edit', 'update', 'destroy']);

    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])
    ->name('sales.cancel');

    Route::resource('users', UserController::class)
        ->only(['index','create','store','show', 'edit', 'update', 'destroy']);
    
    Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

    Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])
    ->name('inventory_movements.index');

    Route::get('/select-company', [CompanySelectionController::class, 'index'])
    ->name('company.select');

    Route::post('/select-company', [CompanySelectionController::class, 'store']);

    Route::get('/select-warehouse', [WarehouseSelectionController::class, 'index'])
    ->name('warehouse.select');

    Route::post('/select-warehouse', [WarehouseSelectionController::class, 'store']);

    Route::middleware(['role:Admin,SuperAdmin'])->group(function () {
    Route::resource('products', ProductController::class);
    });

    Route::middleware(['role:Vendedor,Admin,SuperAdmin'])->group(function () {
        Route::resource('sales', SaleController::class);
    });


    
});

/*
|--------------------------------------------------------------------------
| Catálogos
|--------------------------------------------------------------------------
*/

Route::view('/currencies', 'currencies.index')->name('currencies.index');
Route::view('/companies', 'companies.index')->name('companies.index');
Route::view('/categories', 'categories.index')->name('categories.index');
Route::view('/warehouses', 'warehouses.index')->name('warehouses.index');

/*
|--------------------------------------------------------------------------
| Inventario
|--------------------------------------------------------------------------
*/

Route::view('/inventory', 'inventory.index')->name('inventory.index');
Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])->name('inventory_movements.index');

/*
|--------------------------------------------------------------------------
| Otros
|--------------------------------------------------------------------------
*/

Route::view('/payments', 'payments.index')->name('payments.index');
Route::view('/sale-items', 'sale_items.index')->name('sale_items.index');

require __DIR__.'/auth.php';
