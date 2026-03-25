<?php

use Illuminate\Support\Facades\Route;
use App\Modules\LFSAdministration\UI\Controllers\LFSAdministrationApiController;
use App\Modules\LFSAdministration\UI\Controllers\ApprovalWorkflowsApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('lfs-administration')
    ->name('api.lfs-administration.')
    ->group(function () {
        Route::get('/approval-workflows', [ApprovalWorkflowsApiController::class, 'dashboard'])
            ->name('approval-workflows.dashboard')
            ->middleware('permission:lfs-administration.view');
        Route::get('/approval-workflows/queue/{type}', [ApprovalWorkflowsApiController::class, 'queue'])
            ->name('approval-workflows.queue')
            ->middleware('permission:lfs-administration.view');
        Route::post('/approval-workflows/{approvalId}/approve', [ApprovalWorkflowsApiController::class, 'approve'])
            ->name('approval-workflows.approve')
            ->whereNumber('approvalId')
            ->middleware('permission:lfs-administration.manage');
        Route::post('/approval-workflows/{approvalId}/reject', [ApprovalWorkflowsApiController::class, 'reject'])
            ->name('approval-workflows.reject')
            ->whereNumber('approvalId')
            ->middleware('permission:lfs-administration.manage');

        Route::get('/integration-events', [LFSAdministrationApiController::class, 'integrationEvents'])
            ->name('integration-events')
            ->middleware('permission:lfs-administration.view');

        Route::get('/sync-logs', [LFSAdministrationApiController::class, 'syncLogs'])
            ->name('sync-logs')
            ->middleware('permission:lfs-administration.view');
    });

