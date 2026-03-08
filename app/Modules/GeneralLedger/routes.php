<?php

use Illuminate\Support\Facades\Route;
use App\Modules\GeneralLedger\UI\Controllers\GeneralLedgerController;

Route::middleware(['auth', 'verified', 'permission:general-ledger.view'])
    ->prefix('general-ledger')
    ->name('general-ledger.')
    ->group(function () {
        Route::get('/', [GeneralLedgerController::class, 'index'])->name('index');
        Route::get('/trial-balance', [GeneralLedgerController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/ledger', [GeneralLedgerController::class, 'generalLedger'])->name('ledger');
        Route::get('/income-statement', [GeneralLedgerController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/balance-sheet', [GeneralLedgerController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/cash-flow', [GeneralLedgerController::class, 'cashFlow'])->name('cash-flow');
    });

