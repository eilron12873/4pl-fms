<?php

use Illuminate\Support\Facades\Route;
use App\Modules\InventoryValuation\UI\Controllers\InventoryValuationController;

Route::middleware(['auth', 'verified', 'permission:inventory-valuation.view'])
    ->prefix('inventory-valuation')
    ->name('inventory-valuation.')
    ->group(function () {
        Route::get('/', [InventoryValuationController::class, 'index'])->name('index');
    });

