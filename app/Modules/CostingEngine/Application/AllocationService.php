<?php

namespace App\Modules\CostingEngine\Application;

use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationResult;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRule;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRun;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AllocationService
{
    public function __construct(
        protected ProfitabilityService $profitabilityService,
        protected CostingConfigService $configService,
    ) {
    }

    /**
     * Apply active rules for a date and persist allocation rows.
     */
    public function applyRulesForDate(string $allocationDate): array
    {
        $date = \Carbon\Carbon::parse($allocationDate)->toDateString();
        $rules = CostingAllocationRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('id')
            ->get();

        $created = 0;
        $totalAllocated = 0.0;

        DB::transaction(function () use ($rules, $date, &$created, &$totalAllocated) {
            foreach ($rules as $rule) {
                $rows = $this->targetRowsForRule($rule);
                if ($rows->isEmpty()) {
                    continue;
                }
                $pool = $this->poolAmountForRule($rule);
                if ($pool <= 0) {
                    continue;
                }

                $sumWeight = $rows->sum('weight');
                if ($sumWeight <= 0) {
                    continue;
                }

                foreach ($rows as $row) {
                    $weight = (float) $row['weight'];
                    $amount = round($pool * ($weight / $sumWeight), 2);
                    CostingAllocationResult::create([
                        'rule_id' => $rule->id,
                        'allocation_date' => $date,
                        'target_dimension' => $rule->target_dimension,
                        'target_id' => (int) $row['id'],
                        'allocated_amount' => $amount,
                        'currency' => $this->configService->functionalCurrency(),
                        'meta' => [
                            'rule_type' => $rule->rule_type,
                            'pool_amount' => $pool,
                            'weight' => $weight,
                        ],
                    ]);
                    $created++;
                    $totalAllocated += $amount;
                }
            }
        });

        return [
            'allocation_date' => $date,
            'rows_created' => $created,
            'total_allocated' => round($totalAllocated, 2),
        ];
    }

    /**
     * Apply allocation rules for the provided run (approval-gated).
     *
     * Deletes existing allocation_results for the same run_date to keep reruns deterministic.
     */
    public function applyRulesForRun(CostingAllocationRun $run): array
    {
        return DB::transaction(function () use ($run) {
            $lockedRun = CostingAllocationRun::query()->whereKey($run->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRun->isApproved()) {
                throw new InvalidArgumentException('Only approved allocation runs can be applied.');
            }

            $date = $lockedRun->run_date->toDateString();

            CostingAllocationResult::where('allocation_date', $date)->delete();
            $result = $this->applyRulesForDate($date);

            $meta = $lockedRun->metadata ?? [];
            $lockedRun->update([
                'metadata' => array_merge($meta, [
                    'rows_created' => $result['rows_created'],
                    'total_allocated' => $result['total_allocated'],
                ]),
            ]);

            return $result;
        });
    }

    private function poolAmountForRule(CostingAllocationRule $rule): float
    {
        if ($rule->rule_type === 'fixed') {
            return (float) ($rule->fixed_amount ?? 0);
        }
        if ($rule->rule_type === 'percentage') {
            $pct = (float) ($rule->percentage ?? 0);
            $base = (float) data_get($rule->meta ?? [], 'base_amount', 0);
            return round($base * ($pct / 100), 2);
        }
        return (float) data_get($rule->meta ?? [], 'pool_amount', 0);
    }

    private function targetRowsForRule(CostingAllocationRule $rule): \Illuminate\Support\Collection
    {
        $from = $rule->effective_from?->toDateString();
        $to = $rule->effective_to?->toDateString();
        $dimension = $rule->target_dimension;

        if ($dimension === 'client_id') {
            return $this->profitabilityService->clientProfitability($from, $to)
                ->map(fn ($r) => ['id' => $r['client_id'], 'weight' => $this->weightForRule($rule, $r)]);
        }

        $rows = match ($dimension) {
            'shipment_id' => $this->profitabilityService->shipmentProfitability($from, $to),
            'route_id' => $this->profitabilityService->routeProfitability($from, $to),
            'warehouse_id' => $this->profitabilityService->warehouseProfitability($from, $to),
            'project_id' => $this->profitabilityService->projectProfitability($from, $to),
            default => collect(),
        };

        return $rows->map(fn ($r) => ['id' => $r[$dimension] ?? null, 'weight' => $this->weightForRule($rule, $r)])
            ->filter(fn ($r) => ! empty($r['id']));
    }

    private function weightForRule(CostingAllocationRule $rule, array $row): float
    {
        if ($rule->rule_type === 'revenue_proportion') {
            return max((float) ($row['revenue'] ?? 0), 0.0);
        }
        if ($rule->rule_type === 'volume') {
            return max((float) ($row['cost'] ?? 0), 0.0);
        }
        return 1.0;
    }
}

