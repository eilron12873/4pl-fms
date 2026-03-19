<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\FixedAssets\Application\FixedAssetService;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use App\Modules\FixedAssets\Infrastructure\Models\AssetMaintenance;
use Illuminate\Database\Seeder;

class FixedAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(FixedAssetService::class);

        $demoCodes = [
            'FA-VAN-001',
            'FA-FORK-001',
            'FA-IT-001',
            'FA-DEMO-DISP-GAIN',
            'FA-DEMO-DISP-LOSS',
        ];

        $this->resetDemoData($demoCodes);

        // Active assets
        $van = $service->register([
            'code' => 'FA-VAN-001',
            'name' => 'Delivery van (3.5t)',
            'asset_type' => 'vehicle',
            'purchase_date' => now()->subYears(2)->subMonths(3)->toDateString(),
            'acquisition_cost' => 35000.00,
            'useful_life_years' => 5,
            'residual_value' => 5000.00,
            'location' => 'Main depot',
            'custodian' => 'Fleet manager',
        ]);

        $fork = $service->register([
            'code' => 'FA-FORK-001',
            'name' => 'Forklift (electric)',
            'asset_type' => 'equipment',
            'purchase_date' => now()->subYear()->subMonths(2)->toDateString(),
            'acquisition_cost' => 28000.00,
            'useful_life_years' => 7,
            'residual_value' => 3000.00,
            'location' => 'Main warehouse',
        ]);

        $it = $service->register([
            'code' => 'FA-IT-001',
            'name' => 'Server rack and UPS',
            'asset_type' => 'it',
            'purchase_date' => now()->subMonths(8)->toDateString(),
            'acquisition_cost' => 12000.00,
            'useful_life_years' => 5,
            'residual_value' => 0,
        ]);

        // Disposed assets (gain + loss scenarios)
        $dispGain = $service->register([
            'code' => 'FA-DEMO-DISP-GAIN',
            'name' => 'Disposal gain demo asset',
            'asset_type' => 'equipment',
            'purchase_date' => now()->subMonths(18)->toDateString(),
            'acquisition_cost' => 45000.00,
            'useful_life_years' => 6,
            'residual_value' => 6000.00,
            'location' => 'Demo site',
        ]);

        $dispLoss = $service->register([
            'code' => 'FA-DEMO-DISP-LOSS',
            'name' => 'Disposal loss demo asset',
            'asset_type' => 'equipment',
            'purchase_date' => now()->subMonths(14)->toDateString(),
            'acquisition_cost' => 38000.00,
            'useful_life_years' => 5,
            'residual_value' => 4000.00,
            'location' => 'Demo site',
        ]);

        // Maintenance across months
        $service->recordMaintenance($van->id, now()->subMonths(2)->toDateString(), 450.00, 'Annual service and brake check', 'SRV-VAN-001');
        $service->recordMaintenance($fork->id, now()->subMonths(1)->toDateString(), 320.00, 'Battery inspection and tyre check', 'SRV-FORK-001');
        $service->recordMaintenance($it->id, now()->subMonths(1)->toDateString(), 180.00, 'Cooling fan replacement', 'SRV-IT-001');
        $service->recordMaintenance($dispGain->id, now()->subMonths(2)->toDateString(), 250.00, 'Routine inspection', 'SRV-DISP-GAIN-001');
        $service->recordMaintenance($dispLoss->id, now()->subMonths(1)->toDateString(), 220.00, 'Maintenance prior to disposal', 'SRV-DISP-LOSS-001');

        // Run depreciation for past months so accumulated_depreciation + schedule/history have data.
        $periodEndDates = [
            now()->subMonths(4)->endOfMonth()->toDateString(),
            now()->subMonths(3)->endOfMonth()->toDateString(),
            now()->subMonths(2)->endOfMonth()->toDateString(),
            now()->subMonth()->endOfMonth()->toDateString(),
        ];

        foreach ($periodEndDates as $periodEndDate) {
            $service->runDepreciation($periodEndDate);
        }

        // Dispose the two demo assets at the last seeded period end date.
        $disposedAt = $periodEndDates[array_key_last($periodEndDates)];
        $dispGain->refresh();
        $dispLoss->refresh();

        // Compute proceeds dynamically so the result is deterministic with the seeded depreciation state.
        $gainProceeds = max(0.0, (float) $dispGain->bookValue() + 1000.00);
        $lossProceeds = max(0.0, (float) $dispLoss->bookValue() - 1000.00);

        $service->dispose($dispGain, $gainProceeds, $disposedAt, 'DISP-GAIN-DEMO');
        $service->dispose($dispLoss, $lossProceeds, $disposedAt, 'DISP-LOSS-DEMO');
    }

    /**
     * Reset demo data for deterministic Fixed Assets lifecycle seeding.
     *
     * @param  array<int, string>  $demoCodes
     */
    protected function resetDemoData(array $demoCodes): void
    {
        $assetIds = FixedAsset::whereIn('code', $demoCodes)->pluck('id')->all();
        if (empty($assetIds)) {
            return;
        }

        // Delete financial journals + posting sources created for this module (so idempotency keys don't block and history stays consistent).
        $journalIds = PostingSource::where('source_system', 'fixed-assets')
            ->whereIn('source_reference', array_map('strval', $assetIds))
            ->pluck('journal_id')
            ->unique()
            ->all();

        if (! empty($journalIds)) {
            Journal::whereIn('id', $journalIds)->delete();
        }

        AssetMaintenance::whereIn('fixed_asset_id', $assetIds)->delete();
        FixedAsset::whereIn('code', $demoCodes)->delete();
    }
}
