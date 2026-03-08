<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FinancialReporting\UI\Controllers\FinancialReportingController;

Route::middleware(['auth', 'verified', 'permission:financial-reporting.view'])
    ->prefix('financial-reporting')
    ->name('financial-reporting.')
    ->group(function () {
        Route::get('/', [FinancialReportingController::class, 'index'])->name('index');
        Route::get('/management-reports', [FinancialReportingController::class, 'managementReports'])->name('management-reports');
        Route::get('/tax-summary', [FinancialReportingController::class, 'taxSummary'])->name('tax-summary');
        Route::get('/comparative-income-statement', [FinancialReportingController::class, 'comparativeIncomeStatement'])->name('comparative-income-statement');
        Route::get('/management-pl-dimension', [FinancialReportingController::class, 'managementPlByDimension'])->name('management-pl-dimension');
        Route::get('/cash-flow-analysis', [FinancialReportingController::class, 'cashFlowAnalysis'])->name('cash-flow-analysis');
        Route::get('/kpi-dashboard', [FinancialReportingController::class, 'kpiDashboard'])->name('kpi-dashboard');
    });

