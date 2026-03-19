<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FinancialReporting\UI\Controllers\FinancialReportingApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('financial-reporting')
    ->name('api.financial-reporting.')
    ->group(function () {
        Route::get('/management-reports', [FinancialReportingApiController::class, 'managementReports'])
            ->name('management-reports')
            ->middleware('permission:financial-reporting.view');

        Route::get('/tax-summary', [FinancialReportingApiController::class, 'taxSummary'])
            ->name('tax-summary')
            ->middleware('permission:financial-reporting.view');

        Route::get('/comparative-income-statement', [FinancialReportingApiController::class, 'comparativeIncomeStatement'])
            ->name('comparative-income-statement')
            ->middleware('permission:financial-reporting.view');

        Route::get('/management-pl-dimension', [FinancialReportingApiController::class, 'managementPlByDimension'])
            ->name('management-pl-dimension')
            ->middleware('permission:financial-reporting.view');

        Route::get('/pl-per-revenue', [FinancialReportingApiController::class, 'plPerRevenue'])
            ->name('pl-per-revenue')
            ->middleware('permission:financial-reporting.view');

        Route::get('/cash-flow-analysis', [FinancialReportingApiController::class, 'cashFlowAnalysis'])
            ->name('cash-flow-analysis')
            ->middleware('permission:financial-reporting.view');

        Route::get('/kpi-dashboard', [FinancialReportingApiController::class, 'kpiDashboard'])
            ->name('kpi-dashboard')
            ->middleware('permission:financial-reporting.view');
    });

