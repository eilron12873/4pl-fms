<?php

namespace Tests\Feature\InventoryValuation;

use App\Modules\InventoryValuation\Application\InventoryValuationService;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryBalance;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryMovement;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControlFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbound_issue_requires_negative_quantity(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $wh = Warehouse::create([
            'code' => 'WH-VAL-1',
            'name' => 'Val Warehouse',
            'is_active' => true,
            'notes' => null,
        ]);

        $item = InventoryItem::create([
            'code' => 'INV-WA-VAL-1',
            'name' => 'WA Item',
            'sku' => 'WA-VAL-1',
            'unit' => 'EA',
            'valuation_method' => 'weighted_avg',
            'is_active' => true,
        ]);

        $response = $this->post(route('inventory-valuation.movements.store'), [
            'warehouse_id' => $wh->id,
            'item_id' => $item->id,
            'movement_type' => 'issue',
            'quantity' => 5, // invalid: outbound must be negative
            'unit_cost' => 0,
            'reference' => 'OUT-INVALID',
            'movement_date' => now()->toDateString(),
            'notes' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    public function test_inbound_receipt_requires_unit_cost(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $wh = Warehouse::create([
            'code' => 'WH-VAL-2',
            'name' => 'Val Warehouse 2',
            'is_active' => true,
            'notes' => null,
        ]);

        $item = InventoryItem::create([
            'code' => 'INV-WA-VAL-2',
            'name' => 'WA Item 2',
            'sku' => 'WA-VAL-2',
            'unit' => 'EA',
            'valuation_method' => 'weighted_avg',
            'is_active' => true,
        ]);

        $response = $this->post(route('inventory-valuation.movements.store'), [
            'warehouse_id' => $wh->id,
            'item_id' => $item->id,
            'movement_type' => 'receipt',
            'quantity' => 10,
            // unit_cost omitted intentionally
            'reference' => 'PO-INVALID',
            'movement_date' => now()->toDateString(),
            'notes' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('unit_cost');
    }

    public function test_weighted_avg_issue_cannot_exceed_on_hand(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $wh = Warehouse::create([
            'code' => 'WH-VAL-3',
            'name' => 'Val Warehouse 3',
            'is_active' => true,
            'notes' => null,
        ]);

        $item = InventoryItem::create([
            'code' => 'INV-WA-VAL-3',
            'name' => 'WA Item 3',
            'sku' => 'WA-VAL-3',
            'unit' => 'EA',
            'valuation_method' => 'weighted_avg',
            'is_active' => true,
        ]);

        $service = app(InventoryValuationService::class);
        $service->recordMovement($wh->id, $item->id, 'receipt', 5, 7.0, 'PO-1', now()->toDateString(), null);

        $response = $this->post(route('inventory-valuation.movements.store'), [
            'warehouse_id' => $wh->id,
            'item_id' => $item->id,
            'movement_type' => 'issue',
            'quantity' => -20, // invalid: exceeds on hand
            'unit_cost' => 0,
            'reference' => 'OUT-EXCEED',
            'movement_date' => now()->toDateString(),
            'notes' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    public function test_transfer_workflow_creates_transfer_out_and_transfer_in_and_updates_balances(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $whA = Warehouse::create([
            'code' => 'WH-TR-1',
            'name' => 'Origin',
            'is_active' => true,
            'notes' => null,
        ]);
        $whB = Warehouse::create([
            'code' => 'WH-TR-2',
            'name' => 'Destination',
            'is_active' => true,
            'notes' => null,
        ]);

        $item = InventoryItem::create([
            'code' => 'INV-WA-TR-1',
            'name' => 'Transfer WA Item',
            'sku' => 'TR-WA-1',
            'unit' => 'EA',
            'valuation_method' => 'weighted_avg',
            'is_active' => true,
        ]);

        $service = app(InventoryValuationService::class);
        $service->recordMovement($whA->id, $item->id, 'receipt', 100, 10.00, 'PO-TR-1', now()->toDateString(), null);

        $transferQty = 25.0;
        $transferDate = now()->toDateString();
        $reference = 'TRF-TEST-1';

        $response = $this->post(route('inventory-valuation.movements.store'), [
            'warehouse_id' => $whA->id,
            'item_id' => $item->id,
            'movement_type' => 'transfer',
            'quantity' => $transferQty, // for transfer workflow: positive
            'unit_cost' => 10.00,
            'destination_warehouse_id' => $whB->id,
            'reference' => $reference,
            'movement_date' => $transferDate,
            'notes' => null,
        ]);

        $response->assertRedirect();

        $this->assertSame(
            ['transfer_out', 'transfer_in'],
            InventoryMovement::query()
                ->where('reference', $reference)
                ->orderBy('id')
                ->pluck('movement_type')
                ->values()
                ->all()
        );

        $balanceA = InventoryBalance::where('warehouse_id', $whA->id)
            ->where('item_id', $item->id)
            ->firstOrFail();
        $balanceB = InventoryBalance::where('warehouse_id', $whB->id)
            ->where('item_id', $item->id)
            ->firstOrFail();

        $this->assertEquals(75.0, (float) $balanceA->quantity, '', 0.0001);
        $this->assertEquals(10.0, (float) $balanceA->unit_cost, '', 0.0001);

        $this->assertEquals(25.0, (float) $balanceB->quantity, '', 0.0001);
        $this->assertEquals(10.0, (float) $balanceB->unit_cost, '', 0.0001);
    }
}

