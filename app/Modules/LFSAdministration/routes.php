<?php

use Illuminate\Support\Facades\Route;
use App\Modules\LFSAdministration\UI\Controllers\LFSAdministrationController;

Route::middleware(['auth', 'verified', 'permission:lfs-administration.view'])
    ->prefix('lfs-administration')
    ->name('lfs-administration.')
    ->group(function () {
        Route::get('/', [LFSAdministrationController::class, 'index'])->name('index');
    });

