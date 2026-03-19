<?php

namespace App\Modules\FinancialReporting\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\FinancialReporting\Application\AdvancedReportingService;
use App\Modules\GeneralLedger\Application\ReportingService;
use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\AccountsReceivable\Application\ArReportingService;
use App\Modules\AccountsPayable\Application\ApReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FinancialReportingApiController extends Controller
{
    public function __construct(
        protected AdvancedReportingService $advancedReporting,
        protected ReportingService $reporting,
        protected TreasuryService $treasury,
        protected ArReportingService $arReporting,
        protected ApReportingService $apReporting,
    ) {}

    /**
     * Resolve date boundaries with optional `period` override governance.
     *
     * @return array{fromDate: string, toDate: string}
     */
    private function resolvePeriodOrFromToDates(Request $request, string $defaultFrom, string $defaultTo): array
    {
        $periodCode = $request->filled('period') ? $request->string('period')->toString() : null;

        if ($periodCode) {
            $request->validate([
                'period' => ['required', 'string', 'exists:periods,code'],
            ]);

            $period = Period::where('code', $periodCode)->firstOrFail();

            return [
                'fromDate' => $period->start_date->toDateString(),
                'toDate' => $period->end_date->toDateString(),
            ];
        }

        $inputFrom = $request->filled('from_date') ? $request->string('from_date')->toString() : null;
        $inputTo = $request->filled('to_date') ? $request->string('to_date')->toString() : null;

        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        if ($inputFrom && $inputTo) {
            $from = \Carbon\Carbon::parse($inputFrom);
            $to = \Carbon\Carbon::parse($inputTo);

            if ($from->gt($to)) {
                throw ValidationException::withMessages([
                    'to_date' => ['The to_date must be greater than or equal to from_date.'],
                ]);
            }
        }

        $fromDate = $inputFrom ? \Carbon\Carbon::parse($inputFrom)->toDateString() : $defaultFrom;
        $toDate = $inputTo ? \Carbon\Carbon::parse($inputTo)->toDateString() : $defaultTo;

        return [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ];
    }

    /**
     * Resolve and strictly validate the `dimension` allowlist.
     *
     * @return array{dimension: string}
     */
    private function resolveDimension(Request $request, string $default = 'client_id'): array
    {
        $request->validate([
            'dimension' => ['nullable', 'string', 'in:client_id,warehouse_id,project_id'],
        ]);

        $dimension = $request->filled('dimension') ? $request->string('dimension')->toString() : $default;

        return ['dimension' => $dimension];
    }

    /**
     * @return array<int, array{code: string, start_date: string, end_date: string}>
     */
    private function periodsPayload(): array
    {
        return Period::orderByDesc('start_date')
            ->limit(24)
            ->get(['code', 'start_date', 'end_date'])
            ->map(fn (Period $p) => [
                'code' => (string) $p->code,
                'start_date' => $p->start_date?->toDateString() ?? '',
                'end_date' => $p->end_date?->toDateString() ?? '',
            ])
            ->values()
            ->all();
    }

    public function managementReports(Request $request): JsonResponse
    {
        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );

        $data = $this->advancedReporting->managementSummary($resolved['fromDate'], $resolved['toDate']);

        return response()->json([
            'success' => true,
            'data' => $data,
            'periods' => $this->periodsPayload(),
        ]);
    }

    public function taxSummary(Request $request): JsonResponse
    {
        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );

        $data = $this->advancedReporting->taxSummary($resolved['fromDate'], $resolved['toDate']);

        return response()->json([
            'success' => true,
            'data' => $data,
            'periods' => $this->periodsPayload(),
        ]);
    }

    public function comparativeIncomeStatement(Request $request): JsonResponse
    {
        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );

        $data = $this->advancedReporting->comparativeIncomeStatement($resolved['fromDate'], $resolved['toDate']);

        return response()->json([
            'success' => true,
            'data' => $data,
            'periods' => $this->periodsPayload(),
        ]);
    }

    public function managementPlByDimension(Request $request): JsonResponse
    {
        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );
        $dimension = $this->resolveDimension($request)['dimension'];

        $data = $this->reporting->incomeStatementByDimension($dimension, $resolved['fromDate'], $resolved['toDate']);

        // Deterministic ordering: rows must be stable across requests.
        $data['rows'] = collect($data['rows'] ?? [])
            ->sortBy(fn ($r) => (int) ($r['dimension_id'] ?? 0))
            ->values()
            ->all();

        $data['section_labels'] = collect(config('gl_statements.income_statement', []))
            ->pluck('label', 'key')
            ->all();

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

        return response()->json([
            'success' => true,
            'data' => $data,
            'periods' => $this->periodsPayload(),
        ]);
    }

    public function plPerRevenue(Request $request): JsonResponse
    {
        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );

        $data = $this->reporting->plPerRevenue($resolved['fromDate'], $resolved['toDate']);

        return response()->json([
            'success' => true,
            'data' => $data,
            'periods' => $this->periodsPayload(),
        ]);
    }

    public function cashFlowAnalysis(Request $request): JsonResponse
    {
        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );

        $glCashFlow = $this->reporting->cashFlowIndirect($resolved['fromDate'], $resolved['toDate']);
        $treasuryPosition = $this->treasury->cashPosition();

        return response()->json([
            'success' => true,
            'glCashFlow' => $glCashFlow,
            'treasuryPosition' => $treasuryPosition,
            'from_date' => $resolved['fromDate'],
            'to_date' => $resolved['toDate'],
            'periods' => $this->periodsPayload(),
        ]);
    }

    public function kpiDashboard(Request $request): JsonResponse
    {
        $asOfDate = $request->filled('as_of_date') ? $request->string('as_of_date')->toString() : now()->toDateString();
        $request->validate([
            'as_of_date' => ['nullable', 'date'],
        ]);

        $resolved = $this->resolvePeriodOrFromToDates(
            $request,
            defaultFrom: now()->startOfMonth()->toDateString(),
            defaultTo: now()->toDateString(),
        );

        $arAging = $this->arReporting->agingReport($asOfDate);
        $apAging = $this->apReporting->agingReport($asOfDate);

        // Deterministic tie-breaking: stable sort by total desc, then id asc.
        $arAgingRows = $arAging->all();
        usort($arAgingRows, function ($a, $b) {
            $t1 = (float) ($a['total'] ?? 0);
            $t2 = (float) ($b['total'] ?? 0);
            $epsilon = 0.000001;

            if (abs($t1 - $t2) <= $epsilon) {
                $id1 = (int) ($a['client_id'] ?? 0);
                $id2 = (int) ($b['client_id'] ?? 0);
                return $id1 <=> $id2;
            }

            return $t2 <=> $t1;
        });
        $arAging = collect($arAgingRows)->values();

        $apAgingRows = $apAging->all();
        usort($apAgingRows, function ($a, $b) {
            $t1 = (float) ($a['total'] ?? 0);
            $t2 = (float) ($b['total'] ?? 0);
            $epsilon = 0.000001;

            if (abs($t1 - $t2) <= $epsilon) {
                $id1 = (int) ($a['vendor_id'] ?? 0);
                $id2 = (int) ($b['vendor_id'] ?? 0);
                return $id1 <=> $id2;
            }

            return $t2 <=> $t1;
        });
        $apAging = collect($apAgingRows)->values();

        $arTotal = round((float) $arAging->sum('total'), 2);
        $apTotal = round((float) $apAging->sum('total'), 2);

        $epsilon = 0.0000001;
        $days = max(1, \Carbon\Carbon::parse($resolved['fromDate'])->diffInDays(\Carbon\Carbon::parse($resolved['toDate'])) + 1);
        $comparative = $this->advancedReporting->comparativeIncomeStatement($resolved['fromDate'], $resolved['toDate']);

        // Performance guardrail: reuse comparative totals instead of calling incomeStatement() again.
        $revenueForPeriod = (float) ($comparative['total_revenue_current'] ?? 0);
        $dso = $revenueForPeriod > $epsilon
            ? round(($arTotal / ($revenueForPeriod / $days)), 1)
            : null;

        $marginCurrent = (float) ($comparative['net_income_current'] ?? 0);
        $marginPrior = (float) ($comparative['net_income_prior'] ?? 0);
        $revenueCurrent = (float) ($comparative['total_revenue_current'] ?? 0);
        $marginPctCurrent = abs($revenueCurrent) > $epsilon ? round($marginCurrent / $revenueCurrent * 100, 2) : null;

        $revenuePrior = (float) ($comparative['total_revenue_prior'] ?? 0);
        $marginPctPrior = abs($revenuePrior) > $epsilon ? round($marginPrior / $revenuePrior * 100, 2) : null;

        $marginVariancePct = ($marginPctCurrent !== null && $marginPctPrior !== null)
            ? round($marginPctCurrent - $marginPctPrior, 2)
            : null;

        return response()->json([
            'success' => true,
            'arAging' => $arAging,
            'apAging' => $apAging,
            'arTotal' => $arTotal,
            'apTotal' => $apTotal,
            'dso' => $dso,
            'marginVariancePct' => $marginVariancePct,
            'marginPctCurrent' => $marginPctCurrent,
            'asOfDate' => $asOfDate,
            'fromDate' => $resolved['fromDate'],
            'toDate' => $resolved['toDate'],
        ]);
    }
}

