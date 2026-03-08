<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AccountsReceivable\UI\Controllers\AccountsReceivableController;

Route::middleware(['auth', 'verified', 'permission:accounts-receivable.view'])
    ->prefix('accounts-receivable')
    ->name('accounts-receivable.')
    ->group(function () {
        Route::get('/', [AccountsReceivableController::class, 'index'])->name('index');
        Route::get('/invoices', [AccountsReceivableController::class, 'invoices'])->name('invoices.index');
        Route::get('/invoices/{id}', [AccountsReceivableController::class, 'invoiceShow'])->name('invoices.show')->whereNumber('id');
        Route::post('/invoices/{id}/issue', [AccountsReceivableController::class, 'issueInvoice'])->name('invoices.issue')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::post('/invoices/{id}/credit-note', [AccountsReceivableController::class, 'creditNoteStore'])->name('invoices.credit-note')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::get('/statement', [AccountsReceivableController::class, 'statement'])->name('statement');
        Route::get('/aging', [AccountsReceivableController::class, 'aging'])->name('aging');
        Route::get('/payments', [AccountsReceivableController::class, 'payments'])->name('payments.index');
        Route::get('/payments/create', [AccountsReceivableController::class, 'paymentCreate'])->name('payments.create')->middleware('permission:accounts-receivable.manage');
        Route::post('/payments', [AccountsReceivableController::class, 'paymentStore'])->name('payments.store')->middleware('permission:accounts-receivable.manage');
    });
