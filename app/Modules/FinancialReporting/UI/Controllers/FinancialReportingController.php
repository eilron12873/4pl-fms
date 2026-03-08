<?php

namespace App\Modules\FinancialReporting\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\FinancialReporting\Application\AdvancedReportingService;
use App\Modules\GeneralLedger\Application\ReportingService;
use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\AccountsReceivable\Application\ArReportingService;
use App\Modules\AccountsPayable\Application\ApReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportingController extends Controller
{
    public function __construct(
        protected AdvancedReportingService $advancedReporting,
        protected ReportingService $reporting,
        protected TreasuryService $treasury,
        protected ArReportingService $arReporting,
        protected ApReportingService $apReporting,
    ) {}

    public function index(): View
    {
        return view('financial-reporting::index');
    }

    public function managementReports(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $data = $this->advancedReporting->managementSummary($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('financial-reporting::management-reports', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }

    public function taxSummary(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $data = $this->advancedReporting->taxSummary($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('financial-reporting::tax-summary', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }

    public function comparativeIncomeStatement(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $data = $this->advancedReporting->comparativeIncomeStatement($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('financial-reporting::comparative-income-statement', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }

    public function managementPlByDimension(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $dimension = $request->string('dimension')->toString() ?: 'client_id';
        if (! in_array($dimension, ['client_id', 'warehouse_id', 'project_id'], true)) {
            $dimension = 'client_id';
        }

        $data = $this->reporting->incomeStatementByDimension($dimension, $fromDate, $toDate);
        $data['section_labels'] = collect(config('gl_statements.income_statement', []))->pluck('label', 'key')->all();

        $dimIds = collect($data['rows'] ?? [])->pluck('dimension_id')->unique();
        $labels = [];
        if ($dimension === 'client_id') {
            $clients = \App\Modules\BillingEngine\Infrastructure\Models\BillingClient::whereIn('id', $dimIds)->get()->keyBy('id');
            foreach ($dimIds as $id) {
                $c = $clients->get($id);
                $labels[$id] = $c ? ($c->code . ' - ' . $c->name) : (string) $id;
            }
        } elseif ($dimension === 'warehouse_id') {
            $whs = \App\Modules\InventoryValuation\Infrastructure\Models\Warehouse::whereIn('id', $dimIds)->get()->keyBy('id');
            foreach ($dimIds as $id) {
                $w = $whs->get($id);
                $labels[$id] = $w ? ($w->code . ' - ' . $w->name) : (string) $id;
            }
        } else {
            foreach ($dimIds as $id) {
                $labels[$id] = (string) $id;
            }
        }
        $data['dimension_labels'] = $labels;
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('financial-reporting::management-pl-dimension', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }

    public function plPerRevenue(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $data = $this->reporting->plPerRevenue($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('financial-reporting::pl-per-revenue', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }

    public function cashFlowAnalysis(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();

        $glCashFlow = $this->reporting->cashFlowIndirect($fromDate, $toDate);
        $treasuryPosition = $this->treasury->cashPosition();

        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('financial-reporting::cash-flow-analysis', [
            'glCashFlow' => $glCashFlow,
            'treasuryPosition' => $treasuryPosition,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'periods' => $periods,
        ]);
    }

    public function kpiDashboard(Request $request): View
    {
        $asOfDate = $request->string('as_of_date')->toString() ?: now()->toDateString();
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();

        $arAging = $this->arReporting->agingReport($asOfDate);
        $apAging = $this->apReporting->agingReport($asOfDate);

        $arTotal = $arAging->sum('total');
        $apTotal = $apAging->sum('total');

        $revenueForPeriod = $this->reporting->incomeStatement($fromDate, $toDate)['total_revenue'] ?? 0;
        $days = max(1, \Carbon\Carbon::parse($fromDate)->diffInDays(\Carbon\Carbon::parse($toDate)) + 1);
        $dso = $revenueForPeriod > 0 ? round(($arTotal / ($revenueForPeriod / $days)), 1) : null;

        $comparative = $this->advancedReporting->comparativeIncomeStatement($fromDate, $toDate);
        $marginCurrent = $comparative['net_income_current'] ?? 0;
        $marginPrior = $comparative['net_income_prior'] ?? 0;
        $revenueCurrent = $comparative['total_revenue_current'] ?? 1;
        $marginPctCurrent = $revenueCurrent != 0 ? round($marginCurrent / $revenueCurrent * 100, 2) : null;
        $revenuePrior = $comparative['total_revenue_prior'] ?? 1;
        $marginPctPrior = $revenuePrior != 0 ? round($marginPrior / $revenuePrior * 100, 2) : null;
        $marginVariancePct = ($marginPctCurrent !== null && $marginPctPrior !== null) ? round($marginPctCurrent - $marginPctPrior, 2) : null;

        return view('financial-reporting::kpi-dashboard', [
            'arAging' => $arAging,
            'apAging' => $apAging,
            'arTotal' => $arTotal,
            'apTotal' => $apTotal,
            'dso' => $dso,
            'marginVariancePct' => $marginVariancePct,
            'marginPctCurrent' => $marginPctCurrent,
            'asOfDate' => $asOfDate,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }
}
