<?php

use Illuminate\Support\Facades\Route;
use App\Modules\LFSAdministration\UI\Controllers\LFSAdministrationController;
use App\Modules\LFSAdministration\UI\Controllers\ApprovalWorkflowsController;

Route::middleware(['auth', 'verified', 'permission:lfs-administration.view'])
    ->prefix('lfs-administration')
    ->name('lfs-administration.')
    ->group(function () {
        Route::get('/', [LFSAdministrationController::class, 'index'])->name('index');
        Route::get('/audit-logs', [LFSAdministrationController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/integration-events', [LFSAdministrationController::class, 'integrationEvents'])->name('integration-events');
        Route::get('/sync-logs', [LFSAdministrationController::class, 'syncLogs'])->name('sync-logs');

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

