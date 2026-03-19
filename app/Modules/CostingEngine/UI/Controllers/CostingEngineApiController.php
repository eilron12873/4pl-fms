<?php

namespace App\Modules\CostingEngine\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CostingEngine\Application\ProfitabilityService;
use Illuminate\Http\Request;

class CostingEngineApiController extends Controller
{
    public function __construct(
        protected ProfitabilityService $profitability
    ) {
    }

    public function clientProfitability(Request $request)
    {
        [$from, $to] = $this->validatedDates($request);
        return response()->json($this->profitability->clientProfitability($from, $to)->values());
    }

    public function shipmentProfitability(Request $request)
    {
        [$from, $to] = $this->validatedDates($request);
        return response()->json($this->profitability->shipmentProfitability($from, $to)->values());
    }

    public function routeProfitability(Request $request)
    {
        [$from, $to] = $this->validatedDates($request);
        return response()->json($this->profitability->routeProfitability($from, $to)->values());
    }

    public function warehouseProfitability(Request $request)
    {
        [$from, $to] = $this->validatedDates($request);
        return response()->json($this->profitability->warehouseProfitability($from, $to)->values());
    }

    public function projectProfitability(Request $request)
    {
        [$from, $to] = $this->validatedDates($request);
        return response()->json($this->profitability->projectProfitability($from, $to)->values());
    }

    private function validatedDates(Request $request): array
    {
        $data = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        return [$data['from_date'] ?? null, $data['to_date'] ?? null];
    }
}

