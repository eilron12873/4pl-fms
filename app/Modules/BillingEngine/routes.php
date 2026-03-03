<?php

use Illuminate\Support\Facades\Route;
use App\Modules\BillingEngine\UI\Controllers\BillingEngineController;

Route::middleware(['auth', 'verified', 'permission:billing-engine.view'])
    ->prefix('billing-engine')
    ->name('billing-engine.')
    ->group(function () {
        Route::get('/', [BillingEngineController::class, 'index'])->name('index');
    });

