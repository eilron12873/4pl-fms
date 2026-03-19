<?php

namespace App\Modules\CostingEngine\Application;

use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\CostingEngine\Infrastructure\Repositories\CostingEngineRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProfitabilityService
{
    public function __construct(
        protected CostingEngineRepository $repository,
        protected CostingConfigService $configService,
    ) {
    }

    /**
     * Revenue account code prefixes (41-46 from gl_statements income_statement).
     */
    /**
     * Client profitability: revenue from AR invoices (issued/paid), cost from GL expense lines with client_id.
     *
     * @return Collection<int, array{client_id: int, client_name: string, client_code: string, revenue: float, cost: float, margin: float, margin_pct: float|null}>
     */
    public function clientProfitability(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $cacheKey = $this->cacheKey('client', $fromDate, $toDate);
        return Cache::remember($cacheKey, 300, function () use ($fromDate, $toDate) {
            $revenueByClient = $this->repository->revenueByClientFromInvoices($fromDate, $toDate);
            $costByClient = $this->repository->costByClientFromJournalLines($fromDate, $toDate);

            $clientIds = $revenueByClient->keys()->merge($costByClient->keys())->unique();
            $clients = BillingClient::whereIn('id', $clientIds)->get()->keyBy('id');

            return $clientIds->map(function ($clientId) use ($clients, $revenueByClient, $costByClient) {
                $client = $clients->get($clientId);
                $revenue = (float) ($revenueByClient->get($clientId) ?? 0);
                $cost = (float) ($costByClient->get($clientId) ?? 0);
                $margin = $revenue - $cost;
                $marginPct = $revenue > 0 ? round($margin / $revenue * 100, 2) : null;

                return [
                    'client_id' => $clientId,
                    'client_name' => $client?->name ?? (string) $clientId,
                    'client_code' => $client?->code ?? '',
                    'revenue' => $this->normalizeCurrency($revenue, $client?->currency ?? null),
                    'cost' => $this->normalizeCurrency($cost, $client?->currency ?? null),
                    'margin' => $this->normalizeCurrency($margin, $client?->currency ?? null),
                    'margin_pct' => $marginPct,
                    'currency' => $this->configService->functionalCurrency(),
                ];
            })->sortByDesc('margin')->values();
        });
    }

    /**
     * Warehouse profitability: revenue (credits to revenue accounts) and cost (debits to expense accounts) from journal lines by warehouse_id.
     *
     * @return Collection<int, array{warehouse_id: int, warehouse_code: string, warehouse_name: string, revenue: float, cost: float, margin: float, margin_pct: float|null}>
     */
    public function warehouseProfitability(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $cacheKey = $this->cacheKey('warehouse', $fromDate, $toDate);
        return Cache::remember($cacheKey, 300, function () use ($fromDate, $toDate) {
            $rows = $this->repository->revenueAndCostByDimension('warehouse_id', $fromDate, $toDate);
            $warehouseIds = $rows->pluck('warehouse_id')->filter()->unique();
            $warehouses = \App\Modules\InventoryValuation\Infrastructure\Models\Warehouse::whereIn('id', $warehouseIds)->get()->keyBy('id');

            return $rows->map(function ($row) use ($warehouses) {
                $wid = $row['warehouse_id'];
                $w = $warehouses->get($wid);
                $revenue = (float) ($row['revenue'] ?? 0);
                $cost = (float) ($row['cost'] ?? 0);
                $margin = $revenue - $cost;
                $marginPct = $revenue > 0 ? round($margin / $revenue * 100, 2) : null;

                return [
                    'warehouse_id' => $wid,
                    'warehouse_code' => $w?->code ?? (string) $wid,
                    'warehouse_name' => $w?->name ?? (string) $wid,
                    'revenue' => $this->normalizeCurrency($revenue),
                    'cost' => $this->normalizeCurrency($cost),
                    'margin' => $this->normalizeCurrency($margin),
                    'margin_pct' => $marginPct,
                    'currency' => $this->configService->functionalCurrency(),
                ];
            })->sortByDesc('margin')->values();
        });
    }

    /**
     * Shipment profitability: revenue and cost from journal lines by shipment_id (dimension only; no shipment master).
     *
     * @return Collection<int, array{shipment_id: int, revenue: float, cost: float, margin: float, margin_pct: float|null}>
     */
    public function shipmentProfitability(?string $fromDate = null, ?string $toDate = null): Collection
    {
        return Cache::remember($this->cacheKey('shipment', $fromDate, $toDate), 300, fn () => $this->repository->revenueAndCostByDimension('shipment_id', $fromDate, $toDate)
            ->map(function ($row) {
                $revenue = (float) ($row['revenue'] ?? 0);
                $cost = (float) ($row['cost'] ?? 0);
                $margin = $revenue - $cost;
                $marginPct = $revenue > 0 ? round($margin / $revenue * 100, 2) : null;

                return [
                    'shipment_id' => $row['shipment_id'],
                    'revenue' => $this->normalizeCurrency($revenue),
                    'cost' => $this->normalizeCurrency($cost),
                    'margin' => $this->normalizeCurrency($margin),
                    'margin_pct' => $marginPct,
                    'currency' => $this->configService->functionalCurrency(),
                ];
            })->sortByDesc('margin')->values());
    }

    /**
     * Route profitability: by route_id from journal lines.
     *
     * @return Collection<int, array{route_id: int, revenue: float, cost: float, margin: float, margin_pct: float|null}>
     */
    public function routeProfitability(?string $fromDate = null, ?string $toDate = null): Collection
    {
        return Cache::remember($this->cacheKey('route', $fromDate, $toDate), 300, fn () => $this->repository->revenueAndCostByDimension('route_id', $fromDate, $toDate)
            ->map(function ($row) {
                $revenue = (float) ($row['revenue'] ?? 0);
                $cost = (float) ($row['cost'] ?? 0);
                $margin = $revenue - $cost;
                $marginPct = $revenue > 0 ? round($margin / $revenue * 100, 2) : null;

                return [
                    'route_id' => $row['route_id'],
                    'revenue' => $this->normalizeCurrency($revenue),
                    'cost' => $this->normalizeCurrency($cost),
                    'margin' => $this->normalizeCurrency($margin),
                    'margin_pct' => $marginPct,
                    'currency' => $this->configService->functionalCurrency(),
                ];
            })->sortByDesc('margin')->values());
    }

    /**
     * Project profitability: by project_id from journal lines.
     *
     * @return Collection<int, array{project_id: int, revenue: float, cost: float, margin: float, margin_pct: float|null}>
     */
    public function projectProfitability(?string $fromDate = null, ?string $toDate = null): Collection
    {
        return Cache::remember($this->cacheKey('project', $fromDate, $toDate), 300, fn () => $this->repository->revenueAndCostByDimension('project_id', $fromDate, $toDate)
            ->map(function ($row) {
                $revenue = (float) ($row['revenue'] ?? 0);
                $cost = (float) ($row['cost'] ?? 0);
                $margin = $revenue - $cost;
                $marginPct = $revenue > 0 ? round($margin / $revenue * 100, 2) : null;

                return [
                    'project_id' => $row['project_id'],
                    'revenue' => $this->normalizeCurrency($revenue),
                    'cost' => $this->normalizeCurrency($cost),
                    'margin' => $this->normalizeCurrency($margin),
                    'margin_pct' => $marginPct,
                    'currency' => $this->configService->functionalCurrency(),
                ];
            })->sortByDesc('margin')->values());
    }

    public function detailsByDimension(string $dimension, int|string $id, ?string $fromDate = null, ?string $toDate = null): array
    {
        $journalLines = $this->repository->journalLinesByDimension($dimension, $id, $fromDate, $toDate);
        $invoices = $dimension === 'client_id'
            ? $this->repository->arInvoicesByClient($id, $fromDate, $toDate)
            : collect();

        return [
            'journal_lines' => $journalLines,
            'invoices' => $invoices,
        ];
    }

    private function cacheKey(string $dimension, ?string $fromDate, ?string $toDate): string
    {
        return sprintf('costing:%s:%s:%s:%s', $dimension, $fromDate ?: 'all', $toDate ?: 'all', $this->configService->functionalCurrency());
    }

    private function normalizeCurrency(float $amount, ?string $sourceCurrency = null): float
    {
        $source = strtoupper((string) ($sourceCurrency ?: $this->configService->functionalCurrency()));
        $target = $this->configService->functionalCurrency();
        if ($source === $target) {
            return round($amount, 2);
        }
        $rates = $this->configService->fxRates();
        $sourceRate = (float) ($rates[$source] ?? 1.0);
        $targetRate = (float) ($rates[$target] ?? 1.0);
        if ($sourceRate <= 0 || $targetRate <= 0) {
            return round($amount, 2);
        }
        $usdBase = $amount / $sourceRate;
        return round($usdBase * $targetRate, 2);
    }
}
