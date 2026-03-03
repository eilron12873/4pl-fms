<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AccountsPayable\UI\Controllers\AccountsPayableController;

Route::middleware(['auth', 'verified', 'permission:accounts-payable.view'])
    ->prefix('accounts-payable')
    ->name('accounts-payable.')
    ->group(function () {
        Route::get('/', [AccountsPayableController::class, 'index'])->name('index');
    });

