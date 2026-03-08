<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CoreAccounting\UI\Controllers\CoreAccountingController;

Route::middleware(['auth', 'verified', 'permission:core-accounting.view'])
    ->prefix('core-accounting')
    ->name('core-accounting.')
    ->group(function () {
        Route::get('/', [CoreAccountingController::class, 'index'])->name('index');
        Route::get('/accounts', [CoreAccountingController::class, 'accounts'])->name('accounts.index');
        Route::get('/accounts/{id}', [CoreAccountingController::class, 'accountShow'])->name('accounts.show')->whereNumber('id');
        Route::get('/journals', [CoreAccountingController::class, 'journals'])->name('journals.index');
        Route::get('/journals/{id}', [CoreAccountingController::class, 'journalShow'])->name('journals.show')->whereNumber('id');
        Route::get('/posting-sources', [CoreAccountingController::class, 'postingSources'])->name('posting-sources.index');
        Route::get('/periods', [CoreAccountingController::class, 'periods'])->name('periods.index');
        Route::post('/periods/{id}/close', [CoreAccountingController::class, 'closePeriod'])->name('periods.close')->whereNumber('id')->middleware('permission:core-accounting.manage');
    });

