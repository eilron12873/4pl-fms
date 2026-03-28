<?php

use App\Modules\LFSAdministration\UI\Controllers\ApprovalWorkflowsApiController;
use App\Modules\LFSAdministration\UI\Controllers\LFSAdministrationApiController;
use Illuminate\Support\Facades\Route;

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

        Route::get('/audit-logs', [LFSAdministrationApiController::class, 'auditLogs'])
            ->name('audit-logs')
            ->middleware('permission:lfs-administration.view');

        Route::get('/settings/company', [LFSAdministrationApiController::class, 'settingsCompany'])
            ->name('settings.company')
            ->middleware('permission:lfs-administration.view');
        Route::get('/settings/financial-controls', [LFSAdministrationApiController::class, 'settingsFinancialControls'])
            ->name('settings.financial-controls')
            ->middleware('permission:lfs-administration.view');
        Route::get('/settings/tax', [LFSAdministrationApiController::class, 'settingsTax'])
            ->name('settings.tax')
            ->middleware('permission:lfs-administration.view');
    });
