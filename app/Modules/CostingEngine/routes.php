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
        Route::post('/allocation-engine/rules', [CostingEngineController::class, 'allocationRuleStore'])->name('allocation-rules.store')->middleware('permission:costing-engine.manage');
        Route::get('/details/{dimension}/{id}', [CostingEngineController::class, 'details'])->name('details')->whereNumber('id');
        Route::get('/settings', [CostingEngineController::class, 'settings'])->name('settings')->middleware('permission:costing-engine.manage');
        Route::post('/settings', [CostingEngineController::class, 'settingsUpdate'])->name('settings.update')->middleware('permission:costing-engine.manage');
        Route::post('/presets', [CostingEngineController::class, 'savePreset'])->name('presets.store');
        Route::get('/export/{report}', [CostingEngineController::class, 'exportCsv'])->name('export');
    });

