<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FinancialReporting\UI\Controllers\FinancialReportingController;

Route::middleware(['auth', 'verified', 'permission:financial-reporting.view'])
    ->prefix('financial-reporting')
    ->name('financial-reporting.')
    ->group(function () {
        Route::get('/', [FinancialReportingController::class, 'index'])->name('index');
    });

