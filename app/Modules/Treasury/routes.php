<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Treasury\UI\Controllers\TreasuryController;

Route::middleware(['auth', 'verified', 'permission:treasury.view'])
    ->prefix('treasury')
    ->name('treasury.')
    ->group(function () {
        Route::get('/', [TreasuryController::class, 'index'])->name('index');

        Route::get('/bank-accounts', [TreasuryController::class, 'bankAccounts'])->name('bank-accounts.index');
        Route::get('/bank-accounts/create', [TreasuryController::class, 'bankAccountCreate'])->name('bank-accounts.create')->middleware('permission:treasury.manage');
        Route::post('/bank-accounts', [TreasuryController::class, 'bankAccountStore'])->name('bank-accounts.store')->middleware('permission:treasury.manage');
        Route::get('/bank-accounts/{id}', [TreasuryController::class, 'bankAccountShow'])->name('bank-accounts.show')->whereNumber('id');

        Route::get('/bank-accounts/{accountId}/transactions/create', [TreasuryController::class, 'transactionCreate'])->name('transactions.create')->middleware('permission:treasury.manage');
        Route::post('/transactions', [TreasuryController::class, 'transactionStore'])->name('transactions.store')->middleware('permission:treasury.manage');

        Route::get('/reconciliation', [TreasuryController::class, 'reconciliation'])->name('reconciliation.index');
        Route::post('/reconciliation/match', [TreasuryController::class, 'matchReconciliation'])->name('reconciliation.match')->middleware('permission:treasury.manage');
        Route::post('/reconciliation/unmatch/{statementLineId}', [TreasuryController::class, 'unmatchReconciliation'])->name('reconciliation.unmatch')->whereNumber('statementLineId')->middleware('permission:treasury.manage');
        Route::post('/reconciliation/statement-lines', [TreasuryController::class, 'statementLineStore'])->name('reconciliation.statement-lines.store')->middleware('permission:treasury.manage');
    });
