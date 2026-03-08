<?php

use Illuminate\Support\Facades\Route;
use App\Modules\BillingEngine\UI\Controllers\BillingEngineController;

Route::middleware(['auth', 'verified'])
    ->prefix('billing-engine')
    ->name('billing-engine.')
    ->group(function () {
        Route::get('/', [BillingEngineController::class, 'index'])->name('index')->middleware('permission:billing-engine.view');

        Route::middleware('permission:billing-engine.view')->group(function () {
            Route::get('/clients', [BillingEngineController::class, 'clients'])->name('clients.index');
            Route::get('/rate-simulation', [BillingEngineController::class, 'rateSimulation'])->name('rate-simulation');
            Route::get('/contracts', [BillingEngineController::class, 'contracts'])->name('contracts.index');
            Route::get('/contracts/create', [BillingEngineController::class, 'contractCreate'])->name('contracts.create');
            Route::post('/contracts', [BillingEngineController::class, 'contractStore'])->name('contracts.store')->middleware('permission:billing-engine.manage');
            Route::get('/contracts/{contract}', [BillingEngineController::class, 'contractShow'])->name('contracts.show')->whereNumber('contract');
            Route::get('/contracts/{contract}/edit', [BillingEngineController::class, 'contractEdit'])->name('contracts.edit')->whereNumber('contract');
            Route::put('/contracts/{contract}', [BillingEngineController::class, 'contractUpdate'])->name('contracts.update')->whereNumber('contract')->middleware('permission:billing-engine.manage');
            Route::post('/contracts/{contract}/rates', [BillingEngineController::class, 'storeRateDefinition'])->name('contracts.rates.store')->whereNumber('contract')->middleware('permission:billing-engine.manage');
            Route::delete('/contracts/{contract}/rates/{rate}', [BillingEngineController::class, 'destroyRateDefinition'])->name('contracts.rates.destroy')->whereNumber('contract')->whereNumber('rate')->middleware('permission:billing-engine.manage');
        });

        Route::middleware('permission:billing-engine.manage')->group(function () {
            Route::get('/clients/create', [BillingEngineController::class, 'clientCreate'])->name('clients.create');
            Route::post('/clients', [BillingEngineController::class, 'clientStore'])->name('clients.store');
            Route::get('/clients/{client}/edit', [BillingEngineController::class, 'clientEdit'])->name('clients.edit')->whereNumber('client');
            Route::put('/clients/{client}', [BillingEngineController::class, 'clientUpdate'])->name('clients.update')->whereNumber('client');
        });
    });
