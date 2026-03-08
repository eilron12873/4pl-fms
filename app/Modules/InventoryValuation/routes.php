<?php

use Illuminate\Support\Facades\Route;
use App\Modules\InventoryValuation\UI\Controllers\InventoryValuationController;

Route::middleware(['auth', 'verified', 'permission:inventory-valuation.view'])
    ->prefix('inventory-valuation')
    ->name('inventory-valuation.')
    ->group(function () {
        Route::get('/', [InventoryValuationController::class, 'index'])->name('index');
        Route::get('/valuation', [InventoryValuationController::class, 'valuation'])->name('valuation');

        Route::get('/movements', [InventoryValuationController::class, 'movements'])->name('movements.index');
        Route::get('/movements/create', [InventoryValuationController::class, 'movementCreate'])->name('movements.create')->middleware('permission:inventory-valuation.manage');
        Route::post('/movements', [InventoryValuationController::class, 'movementStore'])->name('movements.store')->middleware('permission:inventory-valuation.manage');

        Route::get('/adjustments', [InventoryValuationController::class, 'adjustments'])->name('adjustments.index');
        Route::get('/adjustments/create', [InventoryValuationController::class, 'adjustmentCreate'])->name('adjustments.create')->middleware('permission:inventory-valuation.manage');
        Route::post('/adjustments', [InventoryValuationController::class, 'adjustmentStore'])->name('adjustments.store')->middleware('permission:inventory-valuation.manage');

        Route::get('/warehouses', [InventoryValuationController::class, 'warehouses'])->name('warehouses.index');
        Route::get('/warehouses/create', [InventoryValuationController::class, 'warehouseCreate'])->name('warehouses.create')->middleware('permission:inventory-valuation.manage');
        Route::post('/warehouses', [InventoryValuationController::class, 'warehouseStore'])->name('warehouses.store')->middleware('permission:inventory-valuation.manage');

        Route::get('/items', [InventoryValuationController::class, 'items'])->name('items.index');
        Route::get('/items/create', [InventoryValuationController::class, 'itemCreate'])->name('items.create')->middleware('permission:inventory-valuation.manage');
        Route::post('/items', [InventoryValuationController::class, 'itemStore'])->name('items.store')->middleware('permission:inventory-valuation.manage');
    });
