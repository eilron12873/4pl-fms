<?php

namespace Tests\Unit\InventoryValuation;

use App\Modules\InventoryValuation\Application\InventoryValuationService;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryBalance;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryFifoLayer;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFifoEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifo_partial_issue_consumes_layers_in_order_and_updates_balance(): void
    {
        $whA = Warehouse::create([
            'code' => 'WH-A',
            'name' => 'Warehouse A',
            'is_active' => true,
            'notes' => null,
        ]);
        $whB = Warehouse::create([
            'code' => 'WH-B',
            'name' => 'Warehouse B',
            'is_active' => true,
            'notes' => null,
        ]);

        $item = InventoryItem::create([
            'code' => 'INV-FIFO-TEST-1',
            'name' => 'FIFO Test Item',
            'sku' => 'FIFO-TEST',
            'unit' => 'EA',
            'valuation_method' => 'fifo',
            'is_active' => true,
        ]);

        $service = app(InventoryValuationService::class);

        $d1 = '2026-01-01';
        $d2 = '2026-01-10';
        $d3 = '2026-01-20';
        $d4 = '2026-01-25';

        // Receipt layer A: 100 @ 1.00
        $service->recordMovement($whA->id, $item->id, 'receipt', 100, 1.00, 'PO-1', $d1, null);
        // Receipt layer B: 50 @ 2.00
        $service->recordMovement($whA->id, $item->id, 'receipt', 50, 2.00, 'PO-2', $d2, null);

        // Issue 120: consumes 100@1.00 + 20@2.00 => remaining 30@2.00
        $service->recordMovement($whA->id, $item->id, 'issue', -120, 0, 'OUT-1', $d3, null);

        $balanceA = InventoryBalance::where('warehouse_id', $whA->id)
            ->where('item_id', $item->id)
            ->firstOrFail();

        $this->assertEquals(30.0, (float) $balanceA->quantity, '', 0.0001);
        $this->assertEquals(2.0, (float) $balanceA->unit_cost, '', 0.0001);

        $layersA = InventoryFifoLayer::query()
            ->where('warehouse_id', $whA->id)
            ->where('item_id', $item->id)
            ->orderBy('layer_date')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $layersA);
        $this->assertEquals(0.0, (float) $layersA[0]->quantity_remaining, '', 0.0001);
        $this->assertEquals(30.0, (float) $layersA[1]->quantity_remaining, '', 0.0001);
        $this->assertEquals(1.0, (float) $layersA[0]->unit_cost, '', 0.0001);
        $this->assertEquals(2.0, (float) $layersA[1]->unit_cost, '', 0.0001);

        // Transfer 10 from A -> B using FIFO effective cost (2.00)
        $service->recordMovement($whA->id, $item->id, 'transfer_out', -10, 0, 'TRF-1', $d4, null);
        $service->recordMovement($whB->id, $item->id, 'transfer_in', 10, 2.00, 'TRF-1', $d4, null);

        $balanceA->refresh();
        $balanceB = InventoryBalance::where('warehouse_id', $whB->id)
            ->where('item_id', $item->id)
            ->firstOrFail();

        $this->assertEquals(20.0, (float) $balanceA->quantity, '', 0.0001);
        $this->assertEquals(2.0, (float) $balanceA->unit_cost, '', 0.0001);

        $this->assertEquals(10.0, (float) $balanceB->quantity, '', 0.0001);
        $this->assertEquals(2.0, (float) $balanceB->unit_cost, '', 0.0001);

        // Report total should equal sum(values) = 20*2 + 10*2 = 60
        $rows = $service->valuationReport(null, $item->id);
        $this->assertEquals(60.0, (float) $rows->sum('value'), '', 0.01);
    }
}

