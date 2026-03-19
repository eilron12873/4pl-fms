<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CostingEngine\UI\Controllers\CostingEngineApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('costing-engine')
    ->name('api.costing-engine.')
    ->group(function () {
        Route::get('/client-profitability', [CostingEngineApiController::class, 'clientProfitability']);
        Route::get('/shipment-profitability', [CostingEngineApiController::class, 'shipmentProfitability']);
        Route::get('/route-profitability', [CostingEngineApiController::class, 'routeProfitability']);
        Route::get('/warehouse-profitability', [CostingEngineApiController::class, 'warehouseProfitability']);
        Route::get('/project-profitability', [CostingEngineApiController::class, 'projectProfitability']);
    });

