<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Treasury\UI\Controllers\TreasuryController;

Route::middleware(['auth', 'verified', 'permission:treasury.view'])
    ->prefix('treasury')
    ->name('treasury.')
    ->group(function () {
        Route::get('/', [TreasuryController::class, 'index'])->name('index');
    });

