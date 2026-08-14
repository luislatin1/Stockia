<?php

use Illuminate\Support\Facades\Route;
use Stockia\Accounting\Http\Controllers\BankAccountController;
use Stockia\Accounting\Http\Controllers\BankController;
use Stockia\Accounting\Http\Controllers\BankMovementController;

Route::middleware(['web', 'auth', 'company.selected', 'role:Admin,SuperAdmin', 'can:accounting'])
    ->prefix('contabilidad')
    ->group(function () {
        Route::resource('bancos', BankController::class)
            ->only(['index', 'create', 'store', 'destroy']);

        Route::resource('cuentas', BankAccountController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::get('cuentas/{bankAccount}/movimientos', [BankMovementController::class, 'index'])
            ->name('bank-accounts.movements.index');

        Route::post('cuentas/{bankAccount}/movimientos', [BankMovementController::class, 'store'])
            ->name('bank-accounts.movements.store');
    });
