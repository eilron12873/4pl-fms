<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Procurement\UI\Controllers\ProcurementController;

Route::middleware(['auth', 'verified', 'permission:procurement.view'])
    ->prefix('procurement')
    ->name('procurement.')
    ->group(function () {
        Route::get('/', [ProcurementController::class, 'index'])->name('index');
        Route::get('/purchase-requests', [ProcurementController::class, 'purchaseRequests'])->name('purchase-requests.index');
        Route::get('/purchase-requests/create', [ProcurementController::class, 'purchaseRequestCreate'])->name('purchase-requests.create')->middleware('permission:procurement.manage');
        Route::post('/purchase-requests', [ProcurementController::class, 'purchaseRequestStore'])->name('purchase-requests.store')->middleware('permission:procurement.manage');
        Route::get('/purchase-requests/{id}', [ProcurementController::class, 'purchaseRequestShow'])->name('purchase-requests.show')->whereNumber('id');
        Route::post('/purchase-requests/{id}/submit', [ProcurementController::class, 'purchaseRequestSubmit'])->name('purchase-requests.submit')->whereNumber('id')->middleware('permission:procurement.manage');
        Route::post('/purchase-requests/{id}/approve', [ProcurementController::class, 'purchaseRequestApprove'])->name('purchase-requests.approve')->whereNumber('id')->middleware('permission:procurement.manage');
        Route::get('/purchase-orders', [ProcurementController::class, 'purchaseOrders'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [ProcurementController::class, 'purchaseOrderCreate'])->name('purchase-orders.create')->middleware('permission:procurement.manage');
        Route::post('/purchase-orders', [ProcurementController::class, 'purchaseOrderStore'])->name('purchase-orders.store')->middleware('permission:procurement.manage');
        Route::post('/purchase-orders/{id}/issue', [ProcurementController::class, 'purchaseOrderIssue'])->name('purchase-orders.issue')->whereNumber('id')->middleware('permission:procurement.manage');
        Route::post('/purchase-orders/{id}/receive', [ProcurementController::class, 'purchaseOrderReceive'])->name('purchase-orders.receive')->whereNumber('id')->middleware('permission:procurement.manage');
        Route::get('/purchase-orders/{id}', [ProcurementController::class, 'purchaseOrderShow'])->name('purchase-orders.show')->whereNumber('id');
    });
