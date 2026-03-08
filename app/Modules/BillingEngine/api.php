<?php

use Illuminate\Support\Facades\Route;
use App\Modules\BillingEngine\UI\Controllers\BillingEngineApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('billing-engine')
    ->name('api.billing-engine.')
    ->group(function () {
        Route::get('/clients', [BillingEngineApiController::class, 'clients'])->name('clients');
        Route::get('/contracts', [BillingEngineApiController::class, 'contracts'])->name('contracts');
        Route::match(['get', 'post'], '/simulate', [BillingEngineApiController::class, 'simulate'])->name('simulate');
    });

