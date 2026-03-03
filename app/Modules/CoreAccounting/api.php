<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CoreAccounting\UI\Controllers\FinancialEventController;

Route::middleware(['auth:sanctum'])
    ->prefix('financial-events')
    ->name('api.financial-events.')
    ->group(function () {
        Route::post('/{event_type}', FinancialEventController::class)
            ->where('event_type', '[-a-zA-Z0-9_]+')
            ->name('handle');
    });

