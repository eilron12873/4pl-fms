<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CoreAccounting\UI\Controllers\CoreAccountingController;

Route::middleware(['auth', 'verified', 'permission:core-accounting.view'])
    ->prefix('core-accounting')
    ->name('core-accounting.')
    ->group(function () {
        Route::get('/', [CoreAccountingController::class, 'index'])->name('index');
    });

