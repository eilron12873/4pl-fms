<?php

namespace Tests\Feature\FixedAssets;

use App\Modules\FixedAssets\Application\FixedAssetService;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetsApiValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_depreciation_run_rejects_non_end_of_month_period_end_date(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('api.fixed-assets.depreciation.run'), [
            'period_end_date' => '2026-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_dispose_rejects_negative_proceeds(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(FixedAssetService::class);
        $asset = $service->register([
            'code' => 'FA-TEST-API-DISP-VAL',
            'name' => 'API dispose validation asset',
            'asset_type' => 'equipment',
            'purchase_date' => now()->subMonths(10)->toDateString(),
            'acquisition_cost' => 2000.00,
            'useful_life_years' => 2,
            'residual_value' => 200.00,
        ]);

        $response = $this->postJson(route('api.fixed-assets.assets.dispose', ['id' => $asset->id]), [
            'proceeds' => -1,
            'disposed_at' => now()->toDateString(),
            'reference' => 'TEST',
        ]);

        $response->assertStatus(422);
    }

    public function test_asset_registration_rejects_residual_value_not_less_than_acquisition_cost(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('fixed-assets.assets.store'), [
            'code' => 'FA-TEST-VAL-RES',
            'name' => 'Residual validation asset',
            'asset_type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'acquisition_cost' => 1000.00,
            'useful_life_years' => 5,
            'residual_value' => 1000.00, // invalid: must be < acquisition_cost
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('residual_value');
    }
}

