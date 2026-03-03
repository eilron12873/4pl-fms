<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AccountsReceivable\UI\Controllers\AccountsReceivableController;

Route::middleware(['auth', 'verified', 'permission:accounts-receivable.view'])
    ->prefix('accounts-receivable')
    ->name('accounts-receivable.')
    ->group(function () {
        Route::get('/', [AccountsReceivableController::class, 'index'])->name('index');
    });

