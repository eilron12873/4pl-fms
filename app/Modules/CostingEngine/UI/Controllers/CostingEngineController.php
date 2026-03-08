<?php

namespace App\Modules\CostingEngine\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CostingEngine\Application\ProfitabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostingEngineController extends Controller
{
    public function __construct(
        protected ProfitabilityService $profitability
    ) {}

    public function index(): View
    {
        return view('costing-engine::index');
    }

    public function clientProfitability(Request $request): View
    {
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $rows = $this->profitability->clientProfitability($from ?: null, $to ?: null);

        return view('costing-engine::client-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function shipmentProfitability(Request $request): View
    {
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $rows = $this->profitability->shipmentProfitability($from ?: null, $to ?: null);

        return view('costing-engine::shipment-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function routeProfitability(Request $request): View
    {
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $rows = $this->profitability->routeProfitability($from ?: null, $to ?: null);

        return view('costing-engine::route-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function warehouseProfitability(Request $request): View
    {
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $rows = $this->profitability->warehouseProfitability($from ?: null, $to ?: null);

        return view('costing-engine::warehouse-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function projectProfitability(Request $request): View
    {
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $rows = $this->profitability->projectProfitability($from ?: null, $to ?: null);

        return view('costing-engine::project-profitability', [
            'rows' => $rows,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function allocationEngine(): View
    {
        return view('costing-engine::allocation-engine');
    }
}
