<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('lfs-administration')
    ->name('api.lfs-administration.')
    ->group(function () {
        // Add LFSAdministration API endpoints here
    });

