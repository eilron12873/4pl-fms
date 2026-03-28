<?php

use App\Modules\AccountsReceivable\UI\Controllers\AccountsReceivableController;
use App\Modules\AccountsReceivable\UI\Controllers\ArClientsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:accounts-receivable.view'])
    ->prefix('accounts-receivable')
    ->name('accounts-receivable.')
    ->group(function () {
        Route::get('/', [AccountsReceivableController::class, 'index'])->name('index');

        Route::get('/clients', [ArClientsController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ArClientsController::class, 'create'])->name('clients.create')->middleware('permission:accounts-receivable.manage|accounts-receivable.clients.manage');
        Route::post('/clients', [ArClientsController::class, 'store'])->name('clients.store')->middleware('permission:accounts-receivable.manage|accounts-receivable.clients.manage');
        Route::get('/clients/{client}', [ArClientsController::class, 'show'])->name('clients.show')->whereNumber('client');
        Route::get('/clients/{client}/edit', [ArClientsController::class, 'edit'])->name('clients.edit')->whereNumber('client')->middleware('permission:accounts-receivable.manage|accounts-receivable.clients.manage');
        Route::put('/clients/{client}', [ArClientsController::class, 'update'])->name('clients.update')->whereNumber('client')->middleware('permission:accounts-receivable.manage|accounts-receivable.clients.manage');
        Route::post('/clients/{client}/toggle-active', [ArClientsController::class, 'toggleActive'])->name('clients.toggle-active')->whereNumber('client')->middleware('permission:accounts-receivable.manage|accounts-receivable.clients.manage');
        Route::delete('/clients/{client}', [ArClientsController::class, 'destroy'])->name('clients.destroy')->whereNumber('client')->middleware('permission:accounts-receivable.manage|accounts-receivable.clients.manage');

        Route::get('/invoices', [AccountsReceivableController::class, 'invoices'])->name('invoices.index');
        Route::get('/invoices/create', [AccountsReceivableController::class, 'invoiceCreate'])->name('invoices.create')->middleware('permission:accounts-receivable.manage');
        Route::post('/invoices', [AccountsReceivableController::class, 'invoiceStore'])->name('invoices.store')->middleware('permission:accounts-receivable.manage');
        Route::get('/invoices/{id}/edit', [AccountsReceivableController::class, 'invoiceEdit'])->name('invoices.edit')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::put('/invoices/{id}', [AccountsReceivableController::class, 'invoiceUpdate'])->name('invoices.update')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::get('/invoices/{id}', [AccountsReceivableController::class, 'invoiceShow'])->name('invoices.show')->whereNumber('id');
        Route::post('/invoices/{id}/issue', [AccountsReceivableController::class, 'issueInvoice'])->name('invoices.issue')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::post('/invoices/{id}/submit-approval', [AccountsReceivableController::class, 'invoiceSubmit'])->name('invoices.submit-approval')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::post('/invoices/{id}/approve', [AccountsReceivableController::class, 'invoiceApprove'])->name('invoices.approve')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::post('/invoices/{id}/reject', [AccountsReceivableController::class, 'invoiceReject'])->name('invoices.reject')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::post('/invoices/{id}/credit-note', [AccountsReceivableController::class, 'creditNoteStore'])->name('invoices.credit-note')->whereNumber('id')->middleware('permission:accounts-receivable.manage');
        Route::get('/statement', [AccountsReceivableController::class, 'statement'])->name('statement');
        Route::get('/aging', [AccountsReceivableController::class, 'aging'])->name('aging');
        Route::get('/payments', [AccountsReceivableController::class, 'payments'])->name('payments.index');
        Route::get('/payments/create', [AccountsReceivableController::class, 'paymentCreate'])->name('payments.create')->middleware('permission:accounts-receivable.manage');
        Route::post('/payments', [AccountsReceivableController::class, 'paymentStore'])->name('payments.store')->middleware('permission:accounts-receivable.manage');
    });
