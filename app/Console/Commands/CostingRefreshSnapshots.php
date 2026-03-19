<?php

namespace App\Console\Commands;

use App\Modules\CostingEngine\Application\ProfitabilityService;
use App\Modules\CostingEngine\Application\CostingConfigService;
use App\Modules\CostingEngine\Infrastructure\Models\CostingProfitabilitySnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CostingRefreshSnapshots extends Command
{
    protected $signature = 'costing:snapshots {--from=} {--to=}';
    protected $description = 'Refresh profitability snapshots by dimension and period';

    public function handle(ProfitabilityService $service, CostingConfigService $config): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $timestamp = now();
        $currency = $config->functionalCurrency();

        $datasets = [
            'client_id' => $service->clientProfitability($from, $to),
            'shipment_id' => $service->shipmentProfitability($from, $to),
            'route_id' => $service->routeProfitability($from, $to),
            'warehouse_id' => $service->warehouseProfitability($from, $to),
            'project_id' => $service->projectProfitability($from, $to),
        ];

        DB::transaction(function () use ($datasets, $from, $to, $timestamp, $currency) {
            foreach ($datasets as $dimension => $rows) {
                foreach ($rows as $row) {
                    $dimensionId = (int) ($row[$dimension] ?? 0);
                    if ($dimensionId <= 0) {
                        continue;
                    }
                    CostingProfitabilitySnapshot::updateOrCreate(
                        [
                            'dimension' => $dimension,
                            'dimension_id' => $dimensionId,
                            'from_date' => $from,
                            'to_date' => $to,
                        ],
                        [
                            'revenue' => (float) ($row['revenue'] ?? 0),
                            'cost' => (float) ($row['cost'] ?? 0),
                            'margin' => (float) ($row['margin'] ?? 0),
                            'margin_pct' => $row['margin_pct'] ?? null,
                            'currency' => $currency,
                            'computed_at' => $timestamp,
                        ]
                    );
                }
            }
        });

        $this->info('Costing snapshots refreshed.');
        return self::SUCCESS;
    }
}

