<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('general-ledger')
    ->name('api.general-ledger.')
    ->group(function () {
        // Add GeneralLedger API endpoints here
    });

