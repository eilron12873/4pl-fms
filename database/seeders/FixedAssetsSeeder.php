<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\FixedAssets\Application\FixedAssetService;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use Illuminate\Database\Seeder;

class FixedAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(FixedAssetService::class);

        $hasAssets = FixedAsset::where('code', 'FA-VAN-001')->exists();
        $hasDepreciation = PostingSource::where('source_system', 'fixed-assets')->where('event_type', 'depreciation')->exists();

        if (! $hasAssets) {
            $service->register([
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

        $service->register([
            'code' => 'FA-FORK-001',
            'name' => 'Forklift (electric)',
            'asset_type' => 'equipment',
            'purchase_date' => now()->subYear()->subMonths(2)->toDateString(),
            'acquisition_cost' => 28000.00,
            'useful_life_years' => 7,
            'residual_value' => 3000.00,
            'location' => 'Main warehouse',
        ]);

        $service->register([
            'code' => 'FA-IT-001',
            'name' => 'Server rack and UPS',
            'asset_type' => 'it',
            'purchase_date' => now()->subMonths(8)->toDateString(),
            'acquisition_cost' => 12000.00,
            'useful_life_years' => 5,
            'residual_value' => 0,
        ]);
        }

        // Run depreciation for 3 past months so schedule/history and reports have data (idempotent per period)
        if (! $hasDepreciation && FixedAsset::where('status', 'active')->exists()) {
            $service->runDepreciation(now()->subMonth()->endOfMonth()->toDateString());
            $service->runDepreciation(now()->subMonths(2)->endOfMonth()->toDateString());
            $service->runDepreciation(now()->subMonths(3)->endOfMonth()->toDateString());

            $van = FixedAsset::where('code', 'FA-VAN-001')->first();
            $fork = FixedAsset::where('code', 'FA-FORK-001')->first();
            if ($van) {
                $service->recordMaintenance($van->id, now()->subMonths(2)->toDateString(), 450.00, 'Annual service and brake check', 'SRV-VAN-001');
            }
            if ($fork) {
                $service->recordMaintenance($fork->id, now()->subMonths(1)->toDateString(), 320.00, 'Battery inspection and tyre check', 'SRV-FORK-001');
            }
        }
    }
}
