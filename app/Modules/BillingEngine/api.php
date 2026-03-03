<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('billing-engine')
    ->name('api.billing-engine.')
    ->group(function () {
        // Add BillingEngine API endpoints here
    });

