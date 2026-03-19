<?php

namespace Tests\Feature\CostingEngine;

use App\Models\User;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingEngineManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_costing_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->withoutMiddleware();
        $this->actingAs($user);

        $response = $this->post(route('costing-engine.settings.update'), [
            'revenue_prefixes' => '41,42,43',
            'expense_prefixes' => '51,52,53',
            'enabled_dimensions' => 'client_id,shipment_id,route_id,warehouse_id,project_id',
            'functional_currency' => 'USD',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('costing_engine_settings', ['setting_key' => 'revenue_prefixes']);
        $this->assertDatabaseHas('costing_engine_settings', ['setting_key' => 'expense_prefixes']);
    }

    public function test_can_create_allocation_rule(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->withoutMiddleware();
        $this->actingAs($user);

        $response = $this->post(route('costing-engine.allocation-rules.store'), [
            'name' => 'Allocation Test',
            'rule_type' => 'fixed',
            'target_dimension' => 'client_id',
            'fixed_amount' => 1000,
            'pool_amount' => 1000,
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('costing_allocation_rules', ['name' => 'Allocation Test', 'rule_type' => 'fixed']);
        $this->assertEquals(1, CostingAllocationRule::count());
    }
}

