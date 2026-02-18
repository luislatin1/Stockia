<?php

use App\Modules\Sales\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin'])->group(function () {
    Route::get('sales/export/excel', [SaleController::class, 'exportExcel'])
        ->name('sales.export.excel');

    Route::resource('sales', SaleController::class)
        ->only(['index','create', 'show', 'edit', 'update', 'destroy']);

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
