<?php

use Illuminate\Support\Facades\Route;
use Stockia\SalesQuotes\Http\Controllers\CustomerOrderController;
use Stockia\SalesQuotes\Http\Controllers\SalesQuoteController;

Route::middleware(['web', 'auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin', 'can:cotizaciones'])
    ->prefix('sales-quotes')
    ->name('salesquotes.')
    ->group(function () {
        Route::get('/', [SalesQuoteController::class, 'index'])->name('index');
        Route::get('/create', [SalesQuoteController::class, 'create'])->name('create');
        Route::post('/', [SalesQuoteController::class, 'store'])->name('store');
        Route::get('/{quote}', [SalesQuoteController::class, 'show'])->name('show');
        Route::post('/{quote}/status', [SalesQuoteController::class, 'updateStatus'])->name('status.update');
    });

Route::middleware(['web', 'auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin', 'can:apartados'])
    ->prefix('customer-orders')
    ->name('customerorders.')
    ->group(function () {
        Route::get('/', [CustomerOrderController::class, 'index'])->name('index');
        Route::get('/create', [CustomerOrderController::class, 'create'])->name('create');
        Route::post('/', [CustomerOrderController::class, 'store'])->name('store');
        Route::get('/{order}', [CustomerOrderController::class, 'show'])->name('show');
        Route::post('/{order}/ready', [CustomerOrderController::class, 'ready'])->name('ready');
        Route::post('/{order}/deliver', [CustomerOrderController::class, 'deliver'])->name('deliver');
        Route::post('/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('cancel');
    });
