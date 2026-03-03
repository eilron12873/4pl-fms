<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CostingEngine\UI\Controllers\CostingEngineController;

Route::middleware(['auth', 'verified', 'permission:costing-engine.view'])
    ->prefix('costing-engine')
    ->name('costing-engine.')
    ->group(function () {
        Route::get('/', [CostingEngineController::class, 'index'])->name('index');
    });

