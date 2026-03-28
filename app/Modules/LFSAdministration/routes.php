<?php

use App\Modules\LFSAdministration\UI\Controllers\ApprovalWorkflowsController;
use App\Modules\LFSAdministration\UI\Controllers\LFSAdministrationController;
use App\Modules\LFSAdministration\UI\Controllers\SystemSettingsController;
use App\Modules\LFSAdministration\UI\Controllers\UsersManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:lfs-administration.view'])
    ->prefix('lfs-administration')
    ->name('lfs-administration.')
    ->group(function () {
        Route::get('/', [LFSAdministrationController::class, 'index'])->name('index');
        Route::get('/audit-logs', [LFSAdministrationController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/audit-logs/export', [LFSAdministrationController::class, 'auditLogsExport'])->name('audit-logs.export');
        Route::get('/audit-logs/{activity}', [LFSAdministrationController::class, 'auditLogShow'])->name('audit-logs.show')->whereNumber('activity');
        Route::get('/integration-events', [LFSAdministrationController::class, 'integrationEvents'])->name('integration-events');
        Route::get('/sync-logs', [LFSAdministrationController::class, 'syncLogs'])->name('sync-logs');

        Route::middleware('permission:lfs-administration.users.view')->group(function () {
            Route::get('/settings/users', [UsersManagementController::class, 'index'])->name('settings.users');
            Route::get('/settings/users/create', [UsersManagementController::class, 'create'])->name('settings.users.create');
            Route::get('/settings/users/{user}/edit', [UsersManagementController::class, 'edit'])->name('settings.users.edit');
        });

        Route::middleware('permission:lfs-administration.users.manage')->group(function () {
            Route::post('/settings/users', [UsersManagementController::class, 'store'])->name('settings.users.store');
            Route::put('/settings/users/{user}', [UsersManagementController::class, 'update'])->name('settings.users.update');
            Route::delete('/settings/users/{user}', [UsersManagementController::class, 'destroy'])->name('settings.users.destroy');
            Route::post('/settings/users/{user}/toggle-active', [UsersManagementController::class, 'toggleActive'])->name('settings.users.toggle-active');
        });

        Route::get('/settings/company', [SystemSettingsController::class, 'company'])->name('settings.company');
        Route::put('/settings/company', [SystemSettingsController::class, 'companyUpdate'])->name('settings.company.update')->middleware('permission:lfs-administration.manage');

        Route::get('/settings/financial-controls', [SystemSettingsController::class, 'financialControls'])->name('settings.financial-controls');
        Route::put('/settings/financial-controls', [SystemSettingsController::class, 'financialControlsUpdate'])->name('settings.financial-controls.update')->middleware('permission:lfs-administration.manage');

        Route::get('/settings/tax', [SystemSettingsController::class, 'taxIndex'])->name('settings.tax');
        Route::get('/settings/tax/codes/create', [SystemSettingsController::class, 'taxCodeCreate'])->name('settings.tax.codes.create')->middleware('permission:lfs-administration.manage');
        Route::post('/settings/tax/codes', [SystemSettingsController::class, 'taxCodeStore'])->name('settings.tax.codes.store')->middleware('permission:lfs-administration.manage');
        Route::get('/settings/tax/codes/{taxCode}/edit', [SystemSettingsController::class, 'taxCodeEdit'])->name('settings.tax.codes.edit')->middleware('permission:lfs-administration.manage');
        Route::put('/settings/tax/codes/{taxCode}', [SystemSettingsController::class, 'taxCodeUpdate'])->name('settings.tax.codes.update')->middleware('permission:lfs-administration.manage');
        Route::post('/settings/tax/codes/{taxCode}/rates', [SystemSettingsController::class, 'taxRateStore'])->name('settings.tax.rates.store')->middleware('permission:lfs-administration.manage');

        // Approval workflows queues
        Route::get('/approval-workflows', [ApprovalWorkflowsController::class, 'index'])
            ->name('approval-workflows.index');
        Route::get('/approval-workflows/vendor-bills', [ApprovalWorkflowsController::class, 'vendorBills'])
            ->name('approval-workflows.vendor-bills');
        Route::get('/approval-workflows/vendor-bills/{billId}', [ApprovalWorkflowsController::class, 'vendorBillShow'])
            ->name('approval-workflows.vendor-bills.show')
            ->whereNumber('billId');
        Route::post('/approval-workflows/vendor-bills/{billId}/approve', [ApprovalWorkflowsController::class, 'vendorBillApprove'])
            ->name('approval-workflows.vendor-bills.approve')
            ->whereNumber('billId')
            ->middleware('permission:lfs-administration.manage');
        Route::post('/approval-workflows/vendor-bills/{billId}/reject', [ApprovalWorkflowsController::class, 'vendorBillReject'])
            ->name('approval-workflows.vendor-bills.reject')
            ->whereNumber('billId')
            ->middleware('permission:lfs-administration.manage');

        Route::get('/approval-workflows/journals', [ApprovalWorkflowsController::class, 'journals'])
            ->name('approval-workflows.journals');
        Route::get('/approval-workflows/journals/{id}', [ApprovalWorkflowsController::class, 'journalShow'])
            ->name('approval-workflows.journals.show')
            ->whereNumber('id');
        Route::post('/approval-workflows/journals/{id}/approve', [ApprovalWorkflowsController::class, 'journalApprove'])
            ->name('approval-workflows.journals.approve')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');
        Route::post('/approval-workflows/journals/{id}/reject', [ApprovalWorkflowsController::class, 'journalReject'])
            ->name('approval-workflows.journals.reject')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');

        Route::get('/approval-workflows/invoices', [ApprovalWorkflowsController::class, 'invoices'])
            ->name('approval-workflows.invoices');
        Route::get('/approval-workflows/invoices/{id}', [ApprovalWorkflowsController::class, 'invoiceShow'])
            ->name('approval-workflows.invoices.show')
            ->whereNumber('id');
        Route::post('/approval-workflows/invoices/{id}/approve', [ApprovalWorkflowsController::class, 'invoiceApprove'])
            ->name('approval-workflows.invoices.approve')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');
        Route::post('/approval-workflows/invoices/{id}/reject', [ApprovalWorkflowsController::class, 'invoiceReject'])
            ->name('approval-workflows.invoices.reject')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');

        Route::get('/approval-workflows/allocations', [ApprovalWorkflowsController::class, 'allocations'])
            ->name('approval-workflows.allocations');
        Route::get('/approval-workflows/allocations/{id}', [ApprovalWorkflowsController::class, 'allocationShow'])
            ->name('approval-workflows.allocations.show')
            ->whereNumber('id');
        Route::post('/approval-workflows/allocations/{id}/approve', [ApprovalWorkflowsController::class, 'allocationApprove'])
            ->name('approval-workflows.allocations.approve')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');
        Route::post('/approval-workflows/allocations/{id}/reject', [ApprovalWorkflowsController::class, 'allocationReject'])
            ->name('approval-workflows.allocations.reject')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');

        Route::get('/approval-workflows/credit-notes', [ApprovalWorkflowsController::class, 'creditNotes'])
            ->name('approval-workflows.credit-notes');
        Route::get('/approval-workflows/credit-notes/{id}', [ApprovalWorkflowsController::class, 'creditNoteShow'])
            ->name('approval-workflows.credit-notes.show')
            ->whereNumber('id');
        Route::post('/approval-workflows/credit-notes/{id}/approve', [ApprovalWorkflowsController::class, 'creditNoteApprove'])
            ->name('approval-workflows.credit-notes.approve')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');
        Route::post('/approval-workflows/credit-notes/{id}/reject', [ApprovalWorkflowsController::class, 'creditNoteReject'])
            ->name('approval-workflows.credit-notes.reject')
            ->whereNumber('id')
            ->middleware('permission:lfs-administration.manage');

        Route::get('/roles', [LFSAdministrationController::class, 'roles'])->name('roles');
        Route::get('/roles/{id}/edit', [LFSAdministrationController::class, 'roleEdit'])->name('roles.edit')->whereNumber('id');
        Route::put('/roles/{id}', [LFSAdministrationController::class, 'roleUpdate'])->name('roles.update')->whereNumber('id')->middleware('permission:lfs-administration.manage');
    });
