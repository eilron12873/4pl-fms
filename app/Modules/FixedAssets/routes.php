<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FixedAssets\UI\Controllers\FixedAssetsController;

Route::middleware(['auth', 'verified', 'permission:fixed-assets.view'])
    ->prefix('fixed-assets')
    ->name('fixed-assets.')
    ->group(function () {
        Route::get('/', [FixedAssetsController::class, 'index'])->name('index');

        Route::get('/assets', [FixedAssetsController::class, 'assets'])->name('assets.index');
        Route::get('/assets/create', [FixedAssetsController::class, 'assetCreate'])->name('assets.create')->middleware('permission:fixed-assets.manage');
        Route::post('/assets', [FixedAssetsController::class, 'assetStore'])->name('assets.store')->middleware('permission:fixed-assets.manage');
        Route::get('/assets/{id}', [FixedAssetsController::class, 'assetShow'])->name('assets.show')->whereNumber('id');

        Route::get('/depreciation', [FixedAssetsController::class, 'depreciation'])->name('depreciation.index');
        Route::get('/depreciation/schedule', [FixedAssetsController::class, 'depreciationSchedule'])->name('depreciation.schedule');
        Route::get('/depreciation/history', [FixedAssetsController::class, 'depreciationHistory'])->name('depreciation.history');
        Route::post('/depreciation/run', [FixedAssetsController::class, 'depreciationRun'])->name('depreciation.run')->middleware('permission:fixed-assets.manage');

        Route::get('/reports', [FixedAssetsController::class, 'reports'])->name('reports.index');

        Route::get('/maintenance', [FixedAssetsController::class, 'maintenance'])->name('maintenance.index');
        Route::get('/maintenance/create', [FixedAssetsController::class, 'maintenanceCreate'])->name('maintenance.create')->middleware('permission:fixed-assets.manage');
        Route::post('/maintenance', [FixedAssetsController::class, 'maintenanceStore'])->name('maintenance.store')->middleware('permission:fixed-assets.manage');
    });
