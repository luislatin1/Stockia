<?php

use App\Http\Controllers\AccountsPayableController;
use App\Http\Controllers\AccountsReceivableController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\WarehouseController;
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
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
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
            Route::middleware(['can:core-admin'])->group(function () {
                Route::get('/core/admin', [CoreAdminController::class, 'index'])->name('core.admin.index');
                Route::patch('/core/admin/company', [CoreAdminController::class, 'updateCompany'])->name('core.admin.company.update');
                Route::post('/core/admin/warehouses', [CoreAdminController::class, 'storeWarehouse'])->name('core.admin.warehouses.store');

                Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
                Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');

                Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
                Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
            });

            Route::middleware(['can:productos'])->group(function () {
                Route::get('products/check-unique', [ProductController::class, 'checkUnique'])
                    ->name('products.check-unique');

                Route::get('products/{product}/adjust', [ProductController::class, 'adjust'])
                    ->name('products.adjust');

                Route::post('products/{product}/adjust', [ProductController::class, 'processAdjustment'])
                    ->name('products.processAdjustment');

                Route::resource('products', ProductController::class)
                    ->only(['index','create','store', 'show', 'edit', 'update', 'destroy']);
            });

            Route::middleware(['can:compras'])->group(function () {
                Route::resource('purchases', PurchaseController::class)
                    ->only(['index', 'create', 'store', 'show']);
                Route::post('/purchases/{purchase}/payments', [PurchaseController::class, 'storePayment'])
                    ->name('purchases.payments.store');
            });

            Route::middleware(['can:cuentas-pagar'])->group(function () {
                Route::get('/cuentas-por-pagar', [AccountsPayableController::class, 'index'])
                    ->name('accounts-payable.index');
                Route::get('/cuentas-por-pagar/{provider}', [AccountsPayableController::class, 'show'])
                    ->name('accounts-payable.show');
            });

            Route::middleware(['can:categorias'])->group(function () {
                Route::resource('categories', CategoryController::class)
                    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            });

            Route::middleware(['can:proveedores'])->group(function () {
                Route::resource('providers', ProviderController::class)
                    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            });

            // Usuarios no es overridable (ver App\Models\Permission::CATALOG) - se
            // queda gateado solo por rol, sin capa de excepciones por usuario.
            Route::resource('users', UserController::class)
                ->only(['index','create','store','show', 'edit', 'update', 'destroy']);
            Route::patch('users/{user}/role', [UserController::class, 'updateRole'])
                ->name('users.role.update');

            Route::middleware(['can:stock-transfers'])->group(function () {
                Route::get('traslados', [StockTransferController::class, 'index'])->name('stock-transfers.index');
                Route::get('traslados/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
                Route::post('traslados', [StockTransferController::class, 'store'])->name('stock-transfers.store');
                Route::get('traslados/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
                Route::post('traslados/{stockTransfer}/recibir', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
                Route::post('traslados/{stockTransfer}/anular', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
            });
        });

        Route::middleware(['role:SuperAdmin'])->prefix('settings')->name('settings.')->group(function () {
            Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
            Route::get('/modules/wizard', [ModuleController::class, 'wizard'])->name('modules.wizard');
            Route::post('/modules/install', [ModuleController::class, 'install'])->name('modules.install');
            Route::patch('/modules/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');
            Route::post('/modules/upload', [ModuleController::class, 'upload'])->name('modules.upload');
        });

        // Permisos no es overridable - solo SuperAdmin administra excepciones,
        // igual que con los módulos.
        Route::middleware(['role:SuperAdmin'])->group(function () {
            Route::get('users/{user}/permissions', [UserPermissionController::class, 'edit'])
                ->name('users.permissions.edit');
            Route::patch('users/{user}/permissions', [UserPermissionController::class, 'update'])
                ->name('users.permissions.update');
        });

        Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])
            ->name('inventory_movements.index')
            ->middleware('can:inventory-movements');

        Route::view('/currencies', 'currencies.index')
            ->name('currencies.index')
            ->middleware('can:monedas');

        Route::view('/inventory', 'inventory.index')
            ->name('inventory.index')
            ->middleware('can:inventory-stub');

        // La vista referencia $sale, que Route::view() no puede proveer - esta
        // pantalla está rota independientemente del gate de acceso (fuera de
        // alcance arreglarla aquí, solo se protege el acceso).
        Route::view('/sale-items', 'sale_items.index')
            ->name('sale_items.index')
            ->middleware('can:sale-items-stub');

        Route::middleware(['role:Vendedor,Admin,SuperAdmin'])->group(function () {
            Route::middleware(['can:ventas'])->group(function () {
                Route::get('sales/export/excel', [SaleController::class, 'exportExcel'])
                    ->name('sales.export.excel');

                Route::resource('sales', SaleController::class)
                    ->only(['index', 'create', 'show', 'edit', 'update', 'destroy']);

                Route::post('sales', [SaleController::class, 'store'])
                    ->middleware('dte.document')
                    ->name('sales.store');

                Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])
                    ->name('sales.ticket');

                Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])
                    ->name('sales.invoice');

                Route::get('sales/{sale}/ticket/pdf', [SaleController::class, 'ticketPdf'])
                    ->name('sales.ticket.pdf');

                Route::get('sales/{sale}/invoice/pdf', [SaleController::class, 'invoicePdf'])
                    ->name('sales.invoice.pdf');

                Route::get('sales/{sale}/admin-adjustment', [SaleController::class, 'adminAdjustment'])
                    ->name('sales.admin-adjustment');

                Route::post('sales/{sale}/admin-adjustment', [SaleController::class, 'storeAdminAdjustment'])
                    ->name('sales.admin-adjustment.store');
            });

            Route::middleware(['role:Admin,SuperAdmin', 'can:cuentas-cobrar'])->group(function () {
                Route::post('sales/{sale}/payments', [SaleController::class, 'storePayment'])
                    ->name('sales.payments.store');

                Route::get('cuentas-por-cobrar', [AccountsReceivableController::class, 'index'])
                    ->name('accounts-receivable.index');

                Route::get('cuentas-por-cobrar/{customer}', [AccountsReceivableController::class, 'show'])
                    ->name('accounts-receivable.show');
            });
        });

    });
});

require __DIR__.'/auth.php';
