<?php

namespace App\Modules\CostingEngine\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CostingEngine\Application\AllocationService;
use App\Modules\CostingEngine\Application\CostingConfigService;
use App\Modules\CostingEngine\Application\ProfitabilityService;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRule;
use App\Modules\CostingEngine\Infrastructure\Models\CostingSavedFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CostingEngineController extends Controller
{
    public function __construct(
        protected ProfitabilityService $profitability,
        protected CostingConfigService $configService,
        protected AllocationService $allocationService,
    ) {
    }

    public function index(): View
    {
        return view('costing-engine::index');
    }

    public function clientProfitability(Request $request): View
    {
        [$from, $to] = $this->validatedDates($request);
        $rows = $this->profitability->clientProfitability($from ?: null, $to ?: null);

        return view('costing-engine::client-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function shipmentProfitability(Request $request): View
    {
        [$from, $to] = $this->validatedDates($request);
        $rows = $this->profitability->shipmentProfitability($from ?: null, $to ?: null);

        return view('costing-engine::shipment-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function routeProfitability(Request $request): View
    {
        [$from, $to] = $this->validatedDates($request);
        $rows = $this->profitability->routeProfitability($from ?: null, $to ?: null);

        return view('costing-engine::route-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function warehouseProfitability(Request $request): View
    {
        [$from, $to] = $this->validatedDates($request);
        $rows = $this->profitability->warehouseProfitability($from ?: null, $to ?: null);

        return view('costing-engine::warehouse-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function projectProfitability(Request $request): View
    {
        [$from, $to] = $this->validatedDates($request);
        $rows = $this->profitability->projectProfitability($from ?: null, $to ?: null);

        return view('costing-engine::project-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function allocationEngine(Request $request): View
    {
        $rules = CostingAllocationRule::query()->orderByDesc('id')->paginate(20);
        $message = null;
        if ($request->filled('run_date')) {
            $result = $this->allocationService->applyRulesForDate($request->string('run_date')->toString());
            $message = __('Allocation run complete: :rows rows, :amount :ccy allocated', [
                'rows' => $result['rows_created'],
                'amount' => number_format((float) $result['total_allocated'], 2),
                'ccy' => $this->configService->functionalCurrency(),
            ]);
        }

        return view('costing-engine::allocation-engine', [
            'rules' => $rules,
            'message' => $message,
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function allocationRuleStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'rule_type' => ['required', 'in:revenue_proportion,volume,fixed,percentage'],
            'target_dimension' => ['required', 'in:client_id,shipment_id,route_id,warehouse_id,project_id'],
            'source_dimension' => ['nullable', 'in:client_id,shipment_id,route_id,warehouse_id,project_id'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pool_amount' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        CostingAllocationRule::create([
            'name' => $data['name'],
            'rule_type' => $data['rule_type'],
            'target_dimension' => $data['target_dimension'],
            'source_dimension' => $data['source_dimension'] ?? null,
            'fixed_amount' => $data['fixed_amount'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'meta' => ['pool_amount' => (float) ($data['pool_amount'] ?? 0)],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('costing-engine.allocation-engine')->with('success', __('Allocation rule created.'));
    }

    public function details(Request $request, string $dimension, int $id): View
    {
        [$from, $to] = $this->validatedDates($request);
        $details = $this->profitability->detailsByDimension($dimension, $id, $from, $to);

        return view('costing-engine::details', [
            'dimension' => $dimension,
            'dimensionId' => $id,
            'details' => $details,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function settings(): View
    {
        return view('costing-engine::settings', [
            'revenuePrefixes' => implode(',', $this->configService->revenuePrefixes()),
            'expensePrefixes' => implode(',', $this->configService->expensePrefixes()),
            'enabledDimensions' => implode(',', $this->configService->enabledDimensions()),
            'functionalCurrency' => $this->configService->functionalCurrency(),
        ]);
    }

    public function settingsUpdate(Request $request)
    {
        $data = $request->validate([
            'revenue_prefixes' => ['required', 'string'],
            'expense_prefixes' => ['required', 'string'],
            'enabled_dimensions' => ['required', 'string'],
            'functional_currency' => ['required', 'string', 'size:3'],
        ]);

        $this->configService->saveSettings([
            'revenue_prefixes' => $this->csvList($data['revenue_prefixes']),
            'expense_prefixes' => $this->csvList($data['expense_prefixes']),
            'enabled_dimensions' => $this->csvList($data['enabled_dimensions']),
            'functional_currency' => [strtoupper($data['functional_currency'])],
        ]);

        return redirect()->route('costing-engine.settings')->with('success', __('Costing settings updated.'));
    }

    public function savePreset(Request $request)
    {
        $data = $request->validate([
            'report_key' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        CostingSavedFilter::create([
            'user_id' => $request->user()->id,
            'report_key' => $data['report_key'],
            'name' => $data['name'],
            'filters' => [
                'from_date' => $data['from_date'] ?? null,
                'to_date' => $data['to_date'] ?? null,
            ],
        ]);

        return back()->with('success', __('Preset saved.'));
    }

    public function exportCsv(Request $request, string $report): Response
    {
        [$from, $to] = $this->validatedDates($request);
        $rows = match ($report) {
            'client' => $this->profitability->clientProfitability($from, $to),
            'shipment' => $this->profitability->shipmentProfitability($from, $to),
            'route' => $this->profitability->routeProfitability($from, $to),
            'warehouse' => $this->profitability->warehouseProfitability($from, $to),
            'project' => $this->profitability->projectProfitability($from, $to),
            default => collect(),
        };

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="costing-' . $report . '.csv"'];
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($out, array_keys((array) $rows->first()));
                foreach ($rows as $row) {
                    fputcsv($out, array_values((array) $row));
                }
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function validatedDates(Request $request): array
    {
        $data = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        return [$data['from_date'] ?? null, $data['to_date'] ?? null];
    }

    private function csvList(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
    }
}
