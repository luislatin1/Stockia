<?php

use Illuminate\Support\Facades\Route;
use Stockia\DteSvMh\Http\Controllers\DteAdminController;
use Stockia\DteSvMh\Http\Controllers\DteCustomerController;
use Stockia\DteSvMh\Http\Controllers\DteVatBooksController;

Route::middleware(['web', 'auth', 'company.selected', 'role:Admin,SuperAdmin'])
    ->prefix('dte')
    ->name('dte.')
    ->group(function () {
        Route::middleware(['can:dte-admin'])->group(function () {
            Route::get('/admin', [DteAdminController::class, 'index'])->name('admin.index');
            Route::patch('/admin/settings', [DteAdminController::class, 'updateSettings'])->name('admin.settings.update');
        });

        Route::middleware(['can:dte-customers'])->group(function () {
            Route::resource('customers', DteCustomerController::class);
        });

        Route::middleware(['can:dte-books'])->group(function () {
            Route::get('/libros-iva', [DteVatBooksController::class, 'index'])->name('books.index');
            Route::get('/libros-iva/{book}/export', [DteVatBooksController::class, 'export'])->name('books.export');
        });
    });
