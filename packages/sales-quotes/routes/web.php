<?php

use Illuminate\Support\Facades\Route;
use Stockia\SalesQuotes\Http\Controllers\SalesQuoteController;

Route::middleware(['web', 'auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin'])
    ->prefix('sales-quotes')
    ->name('salesquotes.')
    ->group(function () {
        Route::get('/', [SalesQuoteController::class, 'index'])->name('index');
        Route::get('/create', [SalesQuoteController::class, 'create'])->name('create');
        Route::post('/', [SalesQuoteController::class, 'store'])->name('store');
        Route::get('/{quote}', [SalesQuoteController::class, 'show'])->name('show');
        Route::post('/{quote}/status', [SalesQuoteController::class, 'updateStatus'])->name('status.update');
    });
