<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BankMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'company.selected', 'role:Admin,SuperAdmin'])
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
