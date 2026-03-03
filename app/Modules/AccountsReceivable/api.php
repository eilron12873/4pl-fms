<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('accounts-receivable')
    ->name('api.accounts-receivable.')
    ->group(function () {
        // Add AccountsReceivable API endpoints here
    });

