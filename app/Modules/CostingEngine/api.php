<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('costing-engine')
    ->name('api.costing-engine.')
    ->group(function () {
        // Add CostingEngine API endpoints here
    });

