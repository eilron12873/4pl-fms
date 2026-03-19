<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Treasury\UI\Controllers\TreasuryApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('treasury')
    ->name('api.treasury.')
    ->group(function () {
        Route::get('/cash-position', [TreasuryApiController::class, 'cashPosition'])
            ->name('cash-position')
            ->middleware('permission:treasury.view');

        Route::post('/bank-accounts', [TreasuryApiController::class, 'bankAccountStore'])
            ->name('bank-accounts.store')
            ->middleware('permission:treasury.manage');

        Route::post('/transactions', [TreasuryApiController::class, 'transactionStore'])
            ->name('transactions.store')
            ->middleware('permission:treasury.manage');

        Route::post('/reconciliation/match', [TreasuryApiController::class, 'reconciliationMatch'])
            ->name('reconciliation.match')
            ->middleware('permission:treasury.manage');

        Route::post('/reconciliation/unmatch/{statementLineId}', [TreasuryApiController::class, 'reconciliationUnmatch'])
            ->name('reconciliation.unmatch')
            ->whereNumber('statementLineId')
            ->middleware('permission:treasury.manage');

        Route::post('/reconciliation/statement-lines', [TreasuryApiController::class, 'statementLineStore'])
            ->name('reconciliation.statement-lines.store')
            ->middleware('permission:treasury.manage');
    });

