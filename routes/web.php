<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanySelectionController;
use App\Http\Controllers\WarehouseSelectionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\CoreAdminController;
use App\Http\Controllers\SetupWizardController;


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
    Route::get('/setup', [SetupWizardController::class, 'step1'])->name('setup.step1');
    Route::post('/setup/step1', [SetupWizardController::class, 'storeStep1'])->name('setup.step1.store');
    Route::get('/setup/step2', [SetupWizardController::class, 'step2'])->name('setup.step2');
    Route::post('/setup/step2', [SetupWizardController::class, 'storeStep2'])->name('setup.step2.store');
    Route::get('/setup/done', [SetupWizardController::class, 'done'])->name('setup.done');

    Route::get('/select-company', [CompanySelectionController::class, 'index'])
    ->name('company.select');

    Route::post('/select-company', [CompanySelectionController::class, 'store']);

    Route::get('/select-warehouse', [WarehouseSelectionController::class, 'index'])
    ->name('warehouse.select');

    Route::post('/select-warehouse', [WarehouseSelectionController::class, 'store']);

    Route::middleware(['company.selected'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::middleware(['role:Admin,SuperAdmin'])->group(function () {
            Route::get('/core/admin', [CoreAdminController::class, 'index'])->name('core.admin.index');
            Route::patch('/core/admin/company', [CoreAdminController::class, 'updateCompany'])->name('core.admin.company.update');
            Route::post('/core/admin/warehouses', [CoreAdminController::class, 'storeWarehouse'])->name('core.admin.warehouses.store');

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
        });

        Route::middleware(['role:SuperAdmin'])->prefix('settings')->name('settings.')->group(function () {
            Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
            Route::get('/modules/wizard', [ModuleController::class, 'wizard'])->name('modules.wizard');
            Route::post('/modules/install', [ModuleController::class, 'install'])->name('modules.install');
            Route::patch('/modules/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');
            Route::post('/modules/upload', [ModuleController::class, 'upload'])->name('modules.upload');
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
