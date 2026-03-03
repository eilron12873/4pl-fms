<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('inventory-valuation')
    ->name('api.inventory-valuation.')
    ->group(function () {
        // Add InventoryValuation API endpoints here
    });

