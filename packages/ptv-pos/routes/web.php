<?php

use Illuminate\Support\Facades\Route;
use Stockia\PTVPos\Http\Controllers\PTVPosController;
use Stockia\PTVPos\Http\Controllers\PTVPosAdminController;

Route::middleware(['web', 'auth', 'company.selected', 'role:Vendedor,Admin,SuperAdmin'])
    ->prefix('ptv-pos')
    ->name('ptvpos.')
    ->group(function () {
        Route::get('/', [PTVPosController::class, 'index'])->name('index');
        Route::get('/pos', [PTVPosController::class, 'pos'])->name('pos');
        Route::get('/cash-movements', [PTVPosController::class, 'cashMovements'])->name('cash-movements.index');
        Route::post('/scan', [PTVPosController::class, 'scan'])->name('scan');
        Route::post('/checkout', [PTVPosController::class, 'checkout'])->name('checkout');
        Route::post('/cash-movements', [PTVPosController::class, 'storeCashMovement'])->name('cash-movements.store');
        Route::post('/admin-auth', [PTVPosController::class, 'adminAuth'])->name('adminAuth');
        Route::get('/sales/{sale}/print', [PTVPosController::class, 'printSale'])->name('sales.print');
        Route::post('/sales/{sale}/print/complete', [PTVPosController::class, 'completePrintedSale'])->name('sales.print.complete');
        Route::get('/open', [PTVPosController::class, 'open'])->name('open');
        Route::post('/open', [PTVPosController::class, 'storeOpen'])->name('open.store');
        Route::get('/close', [PTVPosController::class, 'close'])->name('close');
        Route::post('/close', [PTVPosController::class, 'storeClose'])->name('close.store');
    });

Route::middleware(['web', 'auth', 'company.selected', 'role:Admin,SuperAdmin'])
    ->prefix('ptv-pos/admin')
    ->name('ptvpos.admin.')
    ->group(function () {
        Route::get('/registers', [PTVPosAdminController::class, 'index'])->name('registers.index');
        Route::post('/registers', [PTVPosAdminController::class, 'store'])->name('registers.store');
        Route::get('/templates', [PTVPosController::class, 'templates'])->name('templates.index');
        Route::post('/templates', [PTVPosController::class, 'saveTemplates'])->name('templates.save');
    });
