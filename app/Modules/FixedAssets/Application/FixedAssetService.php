<?php

namespace App\Modules\FixedAssets\Application;

use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\FixedAssets\Infrastructure\Models\AssetMaintenance;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use Carbon\Carbon;

class FixedAssetService
{
    public function __construct(
        protected JournalService $journalService,
    ) {
    }

    public function register(array $data): FixedAsset
    {
        return FixedAsset::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'asset_type' => $data['asset_type'],
            'purchase_date' => $data['purchase_date'],
            'acquisition_cost' => $data['acquisition_cost'],
            'useful_life_years' => (int) $data['useful_life_years'],
            'residual_value' => $data['residual_value'] ?? 0,
            'depreciation_method' => $data['depreciation_method'] ?? FixedAsset::METHOD_STRAIGHT_LINE,
            'gl_asset_code' => $data['gl_asset_code'] ?? '1300',
            'gl_accum_depn_code' => $data['gl_accum_depn_code'] ?? '1320',
            'gl_depn_expense_code' => $data['gl_depn_expense_code'] ?? '5400',
            'status' => FixedAsset::STATUS_ACTIVE,
            'location' => $data['location'] ?? null,
            'custodian' => $data['custodian'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function dispose(FixedAsset $asset, ?string $disposedAt = null): void
    {
        $asset->update([
            'status' => FixedAsset::STATUS_DISPOSED,
            'disposed_at' => $disposedAt ? Carbon::parse($disposedAt) : now(),
        ]);
    }

    /**
     * Straight-line monthly depreciation for one asset for a given period (from_date through to_date).
     * Returns the amount to depreciate (capped by remaining depreciable amount and in-service months).
     */
    public function calculateDepreciationForPeriod(FixedAsset $asset, string $fromDate, string $toDate): float
    {
        if (! $asset->isActive() || $asset->depreciableAmount() <= 0) {
            return 0.0;
        }

        $purchase = Carbon::parse($asset->purchase_date);
        $from = Carbon::parse($fromDate)->startOfMonth();
        $to = Carbon::parse($toDate)->endOfMonth();

        if ($from->gt($to)) {
            return 0.0;
        }

        $monthsTotal = $asset->useful_life_years * 12;
        $monthlyDepn = $asset->depreciableAmount() / $monthsTotal;
        $accumulated = (float) $asset->accumulated_depreciation;
        $maxRemaining = $asset->depreciableAmount() - $accumulated;

        $months = 0;
        $current = $from->copy();
        while ($current->lte($to)) {
            if ($current->gte($purchase)) {
                $months++;
            }
            $current->addMonth();
        }

        $amount = round($monthlyDepn * $months, 2);
        return min(max(0, $amount), $maxRemaining);
    }

    /**
     * Run depreciation for all active assets for the period ending on periodEndDate (one month).
     * Posts one journal per asset with depreciation, updates accumulated_depreciation and last_depreciation_at.
     *
     * @return array<int, array{asset_id: int, asset_code: string, amount: float, journal_id: int|null}>
     */
    public function runDepreciation(string $periodEndDate): array
    {
        $end = Carbon::parse($periodEndDate);
        $fromDate = $end->copy()->startOfMonth()->toDateString();
        $toDate = $end->toDateString();

        $assets = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->get();
        $results = [];

        foreach ($assets as $asset) {
            $amount = $this->calculateDepreciationForPeriod($asset, $fromDate, $toDate);
            $journalId = null;
            $idempotencyKey = 'fa-depn-' . $asset->id . '-' . $fromDate;

            if ($amount > 0 && ! PostingSource::where('idempotency_key', $idempotencyKey)->exists()) {
                $journal = $this->journalService->post([
                    [
                        'account_code' => $asset->gl_depn_expense_code,
                        'description' => 'Depreciation ' . $asset->code . ' ' . $fromDate . ' to ' . $toDate,
                        'debit' => $amount,
                        'credit' => 0,
                    ],
                    [
                        'account_code' => $asset->gl_accum_depn_code,
                        'description' => 'Accumulated depreciation ' . $asset->code,
                        'debit' => 0,
                        'credit' => $amount,
                    ],
                ], [
                    'journal_date' => $toDate,
                    'description' => 'Depreciation ' . $asset->code . ' ' . $fromDate . '-' . $toDate,
                    'source_system' => 'fixed-assets',
                    'source_type' => 'depreciation',
                    'source_reference' => (string) $asset->id,
                    'event_type' => 'depreciation',
                    'idempotency_key' => $idempotencyKey,
                ]);
                $journalId = $journal->id;

                $asset->increment('accumulated_depreciation', $amount);
                $asset->update(['last_depreciation_at' => $toDate]);
            }

            $results[] = [
                'asset_id' => $asset->id,
                'asset_code' => $asset->code,
                'amount' => $amount,
                'journal_id' => $journalId,
            ];
        }

        return $results;
    }

    public function recordMaintenance(int $assetId, string $maintenanceDate, float $amount, ?string $description = null, ?string $reference = null): AssetMaintenance
    {
        return AssetMaintenance::create([
            'fixed_asset_id' => $assetId,
            'maintenance_date' => $maintenanceDate,
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
        ]);
    }
}
