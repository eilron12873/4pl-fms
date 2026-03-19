<?php

namespace Database\Seeders;

use App\Modules\InventoryValuation\Application\InventoryValuationService;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryBalance;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryFifoLayer;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryMovement;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed deterministic demo data for Inventory Control.
     * - Includes both weighted-average and FIFO items
     * - Covers partial consumption, write-offs, adjustments, and transfers
     * - Resets only demo-related movements/balances/layers (not master data)
     */
    /**
     * Seed company-owned inventory (balance sheet assets).
     */
    public function run(): void
    {
        $service = app(InventoryValuationService::class);

        $wh1 = Warehouse::firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Main Warehouse',
                'is_active' => true,
                'notes' => 'Primary storage',
            ]
        );

        $wh2 = Warehouse::firstOrCreate(
            ['code' => 'WH-SEC'],
            [
                'name' => 'Secondary Warehouse',
                'is_active' => true,
                'notes' => null,
            ]
        );

        // Two demo items:
        // - INV-WA-FOAM: weighted-average valuation
        // - INV-FIFO-SCREW: FIFO valuation (partial consumption + transfers)
        $waItem = InventoryItem::firstOrCreate(
            ['code' => 'INV-WA-FOAM'],
            [
                'name' => 'Foam packing insert',
                'sku' => 'FOAM-001',
                'unit' => 'EA',
                'valuation_method' => 'weighted_avg',
                'is_active' => true,
            ]
        );
        $waItem->update([
            'valuation_method' => 'weighted_avg',
            'is_active' => true,
        ]);

        $fifoItem = InventoryItem::firstOrCreate(
            ['code' => 'INV-FIFO-SCREW'],
            [
                'name' => 'Steel screw pack',
                'sku' => 'SCREW-STD',
                'unit' => 'EA',
                'valuation_method' => 'fifo',
                'is_active' => true,
            ]
        );
        $fifoItem->update([
            'valuation_method' => 'fifo',
            'is_active' => true,
        ]);

        // Reset only demo movements/balances/layers for the seeded items/warehouses.
        $this->resetDemoData([$wh1, $wh2], [$waItem, $fifoItem]);

        // --- Weighted Average Item (INV-WA-FOAM) ---
        // Receipts with different costs -> weighted average unit cost.
        $service->recordMovement($wh1->id, $waItem->id, 'receipt', 100, 10.00, 'PO-WA-001', now()->subDays(20)->toDateString(), null);
        $service->recordMovement($wh1->id, $waItem->id, 'receipt', 100, 20.00, 'PO-WA-002', now()->subDays(15)->toDateString(), null);
        $service->recordMovement($wh1->id, $waItem->id, 'issue', -50, 0, 'OUT-WA-001', now()->subDays(10)->toDateString(), 'Assembly use');

        // Transfer (out/in) between warehouses
        // After above: qty=150, unit_cost=15. Transfer out 30 -> dest receives at 15.
        $service->recordMovement($wh1->id, $waItem->id, 'transfer_out', -30, 0, 'TRF-WA-001', now()->subDays(8)->toDateString(), 'Transfer to secondary');
        $service->recordMovement($wh2->id, $waItem->id, 'transfer_in', 30, 15.00, 'TRF-WA-001', now()->subDays(8)->toDateString(), 'Transfer received');

        // Write-off in destination warehouse
        $service->recordMovement($wh2->id, $waItem->id, 'write_off', -10, 0, 'WO-WA-001', now()->subDays(3)->toDateString(), 'Damaged items');

        // Adjustment (add) in origin warehouse
        $service->recordMovement($wh1->id, $waItem->id, 'adjustment', 10, 12.00, 'ADJ-WA-001', now()->subDays(2)->toDateString(), 'Cycle-count correction (add)');

        // --- FIFO Item (INV-FIFO-SCREW) ---
        // Multiple receipts at different costs -> partial issue consumes multiple layers.
        $service->recordMovement($wh1->id, $fifoItem->id, 'receipt', 100, 1.00, 'PO-FIFO-001', now()->subDays(30)->toDateString(), 'Receipt layer A');
        $service->recordMovement($wh1->id, $fifoItem->id, 'receipt', 50, 2.00, 'PO-FIFO-002', now()->subDays(20)->toDateString(), 'Receipt layer B');

        // Partial issue: consume 100@1.00 + 20@2.00 => remaining 30@2.00
        $service->recordMovement($wh1->id, $fifoItem->id, 'issue', -120, 0, 'OUT-FIFO-001', now()->subDays(10)->toDateString(), 'Issue consuming multiple layers');

        // Consume remaining -> balance hits zero
        $service->recordMovement($wh1->id, $fifoItem->id, 'issue', -30, 0, 'OUT-FIFO-002', now()->subDays(5)->toDateString(), 'Final layer consumption');

        // Adjustment (inbound) creates a new layer
        $service->recordMovement($wh1->id, $fifoItem->id, 'adjustment', 10, 1.50, 'ADJ-FIFO-001', now()->subDays(2)->toDateString(), 'Cycle-count correction (add)');

        // Write-off consumes FIFO layer
        $service->recordMovement($wh1->id, $fifoItem->id, 'write_off', -5, 0, 'WO-FIFO-001', now()->subDays(1)->toDateString(), 'Write-off');

        // Transfer remaining (at 1.50) from main to secondary
        $service->recordMovement($wh1->id, $fifoItem->id, 'transfer_out', -3, 0, 'TRF-FIFO-001', now()->subDays(1)->toDateString(), 'Transfer to secondary');
        $service->recordMovement($wh2->id, $fifoItem->id, 'transfer_in', 3, 1.50, 'TRF-FIFO-001', now()->subDays(1)->toDateString(), 'Transfer received');
    }

    /**
     * Reset demo movements/balances/layers for the given demo items.
     *
     * @param  array<int, Warehouse>  $warehouses
     * @param  array<int, InventoryItem>  $items
     */
    protected function resetDemoData(array $warehouses, array $items): void
    {
        $warehouseIds = array_values(array_map(fn ($w) => (int) $w->id, $warehouses));
        $itemIds = array_values(array_map(fn ($i) => (int) $i->id, $items));

        if (empty($warehouseIds) || empty($itemIds)) {
            return;
        }

        InventoryFifoLayer::query()
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('item_id', $itemIds)
            ->delete();

        InventoryMovement::query()
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('item_id', $itemIds)
            ->delete();

        InventoryBalance::query()
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('item_id', $itemIds)
            ->delete();
    }
}
