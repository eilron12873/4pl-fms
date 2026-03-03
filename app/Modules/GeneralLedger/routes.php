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
    });

