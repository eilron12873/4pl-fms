<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('financial-reporting')
    ->name('api.financial-reporting.')
    ->group(function () {
        // Add FinancialReporting API endpoints here
    });

