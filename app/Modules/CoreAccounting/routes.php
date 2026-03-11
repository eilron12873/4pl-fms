<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CoreAccounting\UI\Controllers\CoreAccountingController;

Route::middleware(['auth', 'verified', 'permission:core-accounting.view'])
    ->prefix('core-accounting')
    ->name('core-accounting.')
    ->group(function () {
        Route::get('/', [CoreAccountingController::class, 'index'])->name('index');
        Route::get('/overview.json', [CoreAccountingController::class, 'overviewJson'])->name('overview.json');
        Route::get('/accounts', [CoreAccountingController::class, 'accounts'])->name('accounts.index');
        Route::get('/accounts/{id}', [CoreAccountingController::class, 'accountShow'])->name('accounts.show')->whereNumber('id');
        Route::get('/accounts/create', [CoreAccountingController::class, 'accountsCreate'])->name('accounts.create')->middleware('permission:core-accounting.manage');
        Route::post('/accounts', [CoreAccountingController::class, 'accountsStore'])->name('accounts.store')->middleware('permission:core-accounting.manage');
        Route::get('/accounts/{id}/edit', [CoreAccountingController::class, 'accountsEdit'])->name('accounts.edit')->whereNumber('id')->middleware('permission:core-accounting.manage');
        Route::put('/accounts/{id}', [CoreAccountingController::class, 'accountsUpdate'])->name('accounts.update')->whereNumber('id')->middleware('permission:core-accounting.manage');
        Route::post('/accounts/{id}/deactivate', [CoreAccountingController::class, 'accountsDeactivate'])->name('accounts.deactivate')->whereNumber('id')->middleware('permission:core-accounting.manage');
        Route::get('/accounts/import/template', [CoreAccountingController::class, 'accountsImportTemplate'])->name('accounts.import.template')->middleware('permission:core-accounting.manage');
        Route::post('/accounts/import', [CoreAccountingController::class, 'accountsImport'])->name('accounts.import')->middleware('permission:core-accounting.manage');
        Route::get('/accounts/export', [CoreAccountingController::class, 'accountsExport'])->name('accounts.export');
        Route::get('/journals', [CoreAccountingController::class, 'journals'])->name('journals.index');
        Route::get('/journals/{id}', [CoreAccountingController::class, 'journalShow'])->name('journals.show')->whereNumber('id');
        Route::get('/posting-sources', [CoreAccountingController::class, 'postingSources'])->name('posting-sources.index');
        Route::get('/periods', [CoreAccountingController::class, 'periods'])->name('periods.index');
        Route::post('/periods/{id}/close', [CoreAccountingController::class, 'closePeriod'])->name('periods.close')->whereNumber('id')->middleware('permission:core-accounting.manage');
        Route::get('/posting-rules', [CoreAccountingController::class, 'postingRules'])->name('posting-rules.index');
        Route::get('/posting-rules/create', [CoreAccountingController::class, 'postingRulesCreate'])->name('posting-rules.create')->middleware('permission:core-accounting.manage');
        Route::post('/posting-rules', [CoreAccountingController::class, 'postingRulesStore'])->name('posting-rules.store')->middleware('permission:core-accounting.manage');
        Route::get('/posting-rules/{id}/edit', [CoreAccountingController::class, 'postingRulesEdit'])->name('posting-rules.edit')->whereNumber('id')->middleware('permission:core-accounting.manage');
        Route::put('/posting-rules/{id}', [CoreAccountingController::class, 'postingRulesUpdate'])->name('posting-rules.update')->whereNumber('id')->middleware('permission:core-accounting.manage');
    });

