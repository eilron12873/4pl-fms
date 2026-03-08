<?php

use Illuminate\Support\Facades\Route;
use App\Modules\GeneralLedger\UI\Controllers\GeneralLedgerApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('general-ledger')
    ->name('api.general-ledger.')
    ->group(function () {
        Route::get('/trial-balance', [GeneralLedgerApiController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/ledger', [GeneralLedgerApiController::class, 'ledger'])->name('ledger');
        Route::get('/accounts', [GeneralLedgerApiController::class, 'accounts'])->name('accounts');
    });

