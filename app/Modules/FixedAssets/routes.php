<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FixedAssets\UI\Controllers\FixedAssetsController;

Route::middleware(['auth', 'verified', 'permission:fixed-assets.view'])
    ->prefix('fixed-assets')
    ->name('fixed-assets.')
    ->group(function () {
        Route::get('/', [FixedAssetsController::class, 'index'])->name('index');
    });

