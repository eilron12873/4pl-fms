<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('fixed-assets')
    ->name('api.fixed-assets.')
    ->group(function () {
        // Add FixedAssets API endpoints here
    });

