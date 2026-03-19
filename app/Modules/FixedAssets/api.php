<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FixedAssets\UI\Controllers\FixedAssetsApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('fixed-assets')
    ->name('api.fixed-assets.')
    ->group(function () {
        Route::post('/depreciation/run', [FixedAssetsApiController::class, 'depreciationRun'])
            ->name('depreciation.run')
            ->middleware('permission:fixed-assets.manage');

        Route::post('/maintenance', [FixedAssetsApiController::class, 'maintenanceStore'])
            ->name('maintenance.store')
            ->middleware('permission:fixed-assets.manage');

        Route::post('/assets/{id}/dispose', [FixedAssetsApiController::class, 'disposeAsset'])
            ->name('assets.dispose')
            ->whereNumber('id')
            ->middleware('permission:fixed-assets.manage');
    });

