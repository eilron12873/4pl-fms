<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('accounts-payable')
    ->name('api.accounts-payable.')
    ->group(function () {
        // Add AccountsPayable API endpoints here
    });

