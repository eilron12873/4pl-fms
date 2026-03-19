<?php

namespace App\Modules\FixedAssets\Application;

use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\FixedAssets\Infrastructure\Models\AssetMaintenance;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

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
            'gl_asset_code' => $data['gl_asset_code'] ?? '152500',
            'gl_accum_depn_code' => $data['gl_accum_depn_code'] ?? '153300',
            'gl_depn_expense_code' => $data['gl_depn_expense_code'] ?? '651000',
            'gl_disposal_proceeds_code' => $data['gl_disposal_proceeds_code'] ?? '152600',
            'gl_disposal_gain_code' => $data['gl_disposal_gain_code'] ?? '460000',
            'gl_disposal_loss_code' => $data['gl_disposal_loss_code'] ?? '560000',
            'status' => FixedAsset::STATUS_ACTIVE,
            'location' => $data['location'] ?? null,
            'custodian' => $data['custodian'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Post a GL disposal journal (gain/loss) and mark the asset as disposed.
     *
     * @return void
     */
    public function dispose(
        FixedAsset $asset,
        float $proceeds,
        string $disposedAt,
        ?string $reference = null
    ): void {
        if ($proceeds < 0) {
            throw new InvalidArgumentException('Proceeds must be non-negative.');
        }

        DB::transaction(function () use ($asset, $proceeds, $disposedAt, $reference) {
            $lockedAsset = FixedAsset::query()
                ->where('id', $asset->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAsset->status !== FixedAsset::STATUS_ACTIVE) {
                return; // Already disposed - no-op.
            }

            $disposedDate = Carbon::parse($disposedAt)->toDateString();

            $acquisitionCost = (float) $lockedAsset->acquisition_cost;
            $accumulatedDepn = (float) $lockedAsset->accumulated_depreciation;
            $netBookValue = $acquisitionCost - $accumulatedDepn;

            $gainOrLoss = $proceeds - $netBookValue;
            $gain = $gainOrLoss > 0 ? round($gainOrLoss, 2) : 0.0;
            $loss = $gainOrLoss < 0 ? round(abs($gainOrLoss), 2) : 0.0;

            $idempotencyKey = 'fa-dispose-' . $lockedAsset->id . '-' . $disposedDate;
            if (PostingSource::where('idempotency_key', $idempotencyKey)->exists()) {
                $lockedAsset->update([
                    'status' => FixedAsset::STATUS_DISPOSED,
                    'disposed_at' => Carbon::parse($disposedDate),
                ]);

                return;
            }

            $lines = [
                [
                    'account_code' => $lockedAsset->gl_disposal_proceeds_code,
                    'description' => 'Asset disposal proceeds ' . $lockedAsset->code,
                    'debit' => $proceeds,
                    'credit' => 0,
                ],
                [
                    'account_code' => $lockedAsset->gl_accum_depn_code,
                    'description' => 'Remove accumulated depreciation ' . $lockedAsset->code,
                    'debit' => $accumulatedDepn,
                    'credit' => 0,
                ],
                [
                    'account_code' => $lockedAsset->gl_asset_code,
                    'description' => 'Remove fixed asset ' . $lockedAsset->code,
                    'debit' => 0,
                    'credit' => $acquisitionCost,
                ],
            ];

            if ($gain > 0) {
                $lines[] = [
                    'account_code' => $lockedAsset->gl_disposal_gain_code,
                    'description' => 'Disposal gain ' . $lockedAsset->code,
                    'debit' => 0,
                    'credit' => $gain,
                ];
            } elseif ($loss > 0) {
                $lines[] = [
                    'account_code' => $lockedAsset->gl_disposal_loss_code,
                    'description' => 'Disposal loss ' . $lockedAsset->code,
                    'debit' => $loss,
                    'credit' => 0,
                ];
            }

            $this->journalService->post(
                $lines,
                [
                    'journal_date' => $disposedDate,
                    'description' => 'Asset disposal ' . $lockedAsset->code,
                    'source_system' => 'fixed-assets',
                    'source_type' => 'disposal',
                    'source_reference' => (string) $lockedAsset->id,
                    'event_type' => 'disposal',
                    'idempotency_key' => $idempotencyKey,
                    'payload' => [
                        'reference' => $reference,
                        'proceeds' => $proceeds,
                        'gain' => $gain,
                        'loss' => $loss,
                    ],
                ]
            );

            $lockedAsset->update([
                'status' => FixedAsset::STATUS_DISPOSED,
                'disposed_at' => Carbon::parse($disposedDate),
            ]);
        });
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
        $parsed = Carbon::parse($periodEndDate);
        $end = $parsed->copy()->endOfMonth();

        if ($parsed->toDateString() !== $end->toDateString()) {
            throw new InvalidArgumentException('period_end_date must be the end of the month.');
        }

        $fromDate = $end->copy()->startOfMonth()->toDateString();
        $toDate = $end->toDateString();

        $assetIds = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->pluck('id')->all();
        $results = [];

        foreach ($assetIds as $assetId) {
            $results[] = DB::transaction(function () use ($assetId, $fromDate, $toDate) {
                $lockedAsset = FixedAsset::query()
                    ->where('id', $assetId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedAsset->status !== FixedAsset::STATUS_ACTIVE) {
                    return [
                        'asset_id' => $lockedAsset->id,
                        'asset_code' => $lockedAsset->code,
                        'amount' => 0.0,
                        'journal_id' => null,
                    ];
                }

                $idempotencyKey = 'fa-depn-' . $lockedAsset->id . '-' . $fromDate;
                if (PostingSource::where('idempotency_key', $idempotencyKey)->exists()) {
                    return [
                        'asset_id' => $lockedAsset->id,
                        'asset_code' => $lockedAsset->code,
                        'amount' => 0.0,
                        'journal_id' => null,
                    ];
                }

                $amount = $this->calculateDepreciationForPeriod($lockedAsset, $fromDate, $toDate);
                $journalId = null;

                if ($amount > 0) {
                    $journal = $this->journalService->post([
                        [
                            'account_code' => $lockedAsset->gl_depn_expense_code,
                            'description' => 'Depreciation ' . $lockedAsset->code . ' ' . $fromDate . ' to ' . $toDate,
                            'debit' => $amount,
                            'credit' => 0,
                        ],
                        [
                            'account_code' => $lockedAsset->gl_accum_depn_code,
                            'description' => 'Accumulated depreciation ' . $lockedAsset->code,
                            'debit' => 0,
                            'credit' => $amount,
                        ],
                    ], [
                        'journal_date' => $toDate,
                        'description' => 'Depreciation ' . $lockedAsset->code . ' ' . $fromDate . '-' . $toDate,
                        'source_system' => 'fixed-assets',
                        'source_type' => 'depreciation',
                        'source_reference' => (string) $lockedAsset->id,
                        'event_type' => 'depreciation',
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    $journalId = $journal->id;

                    $lockedAsset->increment('accumulated_depreciation', $amount);
                    $lockedAsset->update(['last_depreciation_at' => $toDate]);
                }

                return [
                    'asset_id' => $lockedAsset->id,
                    'asset_code' => $lockedAsset->code,
                    'amount' => $amount,
                    'journal_id' => $journalId,
                ];
            });
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
