<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AccountsPayable\UI\Controllers\AccountsPayableController;

Route::middleware(['auth', 'verified', 'permission:accounts-payable.view'])
    ->prefix('accounts-payable')
    ->name('accounts-payable.')
    ->group(function () {
        Route::get('/', [AccountsPayableController::class, 'index'])->name('index');

        Route::get('/vendors', [AccountsPayableController::class, 'vendors'])->name('vendors.index');
        Route::get('/vendors/create', [AccountsPayableController::class, 'vendorCreate'])->name('vendors.create')->middleware('permission:accounts-payable.manage');
        Route::post('/vendors', [AccountsPayableController::class, 'vendorStore'])->name('vendors.store')->middleware('permission:accounts-payable.manage');

        Route::get('/bills', [AccountsPayableController::class, 'bills'])->name('bills.index');
        Route::get('/bills/{id}', [AccountsPayableController::class, 'billShow'])->name('bills.show')->whereNumber('id');
        Route::post('/bills/{id}/issue', [AccountsPayableController::class, 'issueBill'])->name('bills.issue')->whereNumber('id')->middleware('permission:accounts-payable.manage');
        Route::post('/bills/{id}/credit-note', [AccountsPayableController::class, 'creditNoteStore'])->name('bills.credit-note')->whereNumber('id')->middleware('permission:accounts-payable.manage');

        Route::get('/statement', [AccountsPayableController::class, 'statement'])->name('statement');
        Route::get('/aging', [AccountsPayableController::class, 'aging'])->name('aging');

        Route::get('/payments', [AccountsPayableController::class, 'payments'])->name('payments.index');
        Route::get('/payments/create', [AccountsPayableController::class, 'paymentCreate'])->name('payments.create')->middleware('permission:accounts-payable.manage');
        Route::post('/payments', [AccountsPayableController::class, 'paymentStore'])->name('payments.store')->middleware('permission:accounts-payable.manage');
    });
