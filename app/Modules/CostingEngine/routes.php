<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CostingEngine\UI\Controllers\CostingEngineController;

Route::middleware(['auth', 'verified', 'permission:costing-engine.view'])
    ->prefix('costing-engine')
    ->name('costing-engine.')
    ->group(function () {
        Route::get('/', [CostingEngineController::class, 'index'])->name('index');
        Route::get('/client-profitability', [CostingEngineController::class, 'clientProfitability'])->name('client-profitability');
        Route::get('/shipment-profitability', [CostingEngineController::class, 'shipmentProfitability'])->name('shipment-profitability');
        Route::get('/route-profitability', [CostingEngineController::class, 'routeProfitability'])->name('route-profitability');
        Route::get('/warehouse-profitability', [CostingEngineController::class, 'warehouseProfitability'])->name('warehouse-profitability');
        Route::get('/project-profitability', [CostingEngineController::class, 'projectProfitability'])->name('project-profitability');
        Route::get('/allocation-engine', [CostingEngineController::class, 'allocationEngine'])->name('allocation-engine');
    });

