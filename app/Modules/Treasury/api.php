<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('treasury')
    ->name('api.treasury.')
    ->group(function () {
        // Add Treasury API endpoints here
    });

