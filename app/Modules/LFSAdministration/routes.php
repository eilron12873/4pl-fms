<?php

use Illuminate\Support\Facades\Route;
use App\Modules\LFSAdministration\UI\Controllers\LFSAdministrationController;

Route::middleware(['auth', 'verified', 'permission:lfs-administration.view'])
    ->prefix('lfs-administration')
    ->name('lfs-administration.')
    ->group(function () {
        Route::get('/', [LFSAdministrationController::class, 'index'])->name('index');
        Route::get('/audit-logs', [LFSAdministrationController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/integration-events', [LFSAdministrationController::class, 'integrationEvents'])->name('integration-events');
        Route::get('/sync-logs', [LFSAdministrationController::class, 'syncLogs'])->name('sync-logs');
        Route::get('/roles', [LFSAdministrationController::class, 'roles'])->name('roles');
        Route::get('/roles/{id}/edit', [LFSAdministrationController::class, 'roleEdit'])->name('roles.edit')->whereNumber('id');
        Route::put('/roles/{id}', [LFSAdministrationController::class, 'roleUpdate'])->name('roles.update')->whereNumber('id')->middleware('permission:lfs-administration.manage');
    });

