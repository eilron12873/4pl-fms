<?php

namespace Database\Seeders;

use App\Modules\InventoryValuation\Application\InventoryValuationService;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed company-owned inventory (balance sheet assets).
     * Typical examples: packaging materials, spare parts, consumables.
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

        // Packaging – pallets (company-owned, balance sheet asset)
        $pallets = InventoryItem::firstOrCreate(
            ['code' => 'INV-PALLET'],
            [
                'name' => 'Wooden pallet (1200x800)',
                'sku' => 'PAL-STD',
                'unit' => 'EA',
                'valuation_method' => 'weighted_average',
                'is_active' => true,
            ]
        );

        // Packaging – stretch wrap
        $stretchWrap = InventoryItem::firstOrCreate(
            ['code' => 'INV-STRETCH'],
            [
                'name' => 'Stretch wrap roll (500mm)',
                'sku' => 'WRAP-500',
                'unit' => 'ROLL',
                'valuation_method' => 'weighted_average',
                'is_active' => true,
            ]
        );

        // Packaging – carton boxes
        $cartons = InventoryItem::firstOrCreate(
            ['code' => 'INV-CARTON'],
            [
                'name' => 'Carton box (assorted)',
                'sku' => 'CTN-ASST',
                'unit' => 'EA',
                'valuation_method' => 'weighted_average',
                'is_active' => true,
            ]
        );

        // Consumables – packing tape
        $tape = InventoryItem::firstOrCreate(
            ['code' => 'INV-TAPE'],
            [
                'name' => 'Packing tape roll',
                'sku' => 'TAPE-48',
                'unit' => 'ROLL',
                'valuation_method' => 'weighted_average',
                'is_active' => true,
            ]
        );

        // Spare parts / consumables – e.g. forklift or equipment
        $sparePart = InventoryItem::firstOrCreate(
            ['code' => 'INV-SPARE'],
            [
                'name' => 'Forklift filter (oil/air)',
                'sku' => 'SP-FLT-01',
                'unit' => 'EA',
                'valuation_method' => 'weighted_average',
                'is_active' => true,
            ]
        );

        // --- Pallets: receipts and issues, valued cost ---
        if ($pallets->movements()->count() === 0) {
            $service->recordMovement($wh1->id, $pallets->id, 'receipt', 200, 12.50, 'PO-PAL-001', now()->subDays(30)->toDateString(), 'Initial pallet stock');
            $service->recordMovement($wh1->id, $pallets->id, 'issue', -45, 0, 'OUT-OPS', now()->subDays(14)->toDateString(), 'Operations use');
            $service->recordMovement($wh1->id, $pallets->id, 'receipt', 80, 13.00, 'PO-PAL-002', now()->subDays(7)->toDateString(), null);
        }

        // --- Stretch wrap: receipts and issues ---
        if ($stretchWrap->movements()->count() === 0) {
            $service->recordMovement($wh1->id, $stretchWrap->id, 'receipt', 120, 8.75, 'PO-WRAP-01', now()->subDays(21)->toDateString(), null);
            $service->recordMovement($wh1->id, $stretchWrap->id, 'issue', -30, 0, 'OUT-OPS', now()->subDays(10)->toDateString(), 'Packing');
            $service->recordMovement($wh2->id, $stretchWrap->id, 'transfer_in', 20, 8.75, 'TRF-01', now()->subDays(5)->toDateString(), 'Transfer to secondary');
        }

        // --- Cartons: receipts and issues ---
        if ($cartons->movements()->count() === 0) {
            $service->recordMovement($wh1->id, $cartons->id, 'receipt', 500, 1.20, 'PO-CTN-01', now()->subDays(14)->toDateString(), 'Assorted cartons');
            $service->recordMovement($wh1->id, $cartons->id, 'issue', -120, 0, 'OUT-OPS', now()->subDays(3)->toDateString(), 'Order packing');
        }

        // --- Tape: receipts and issues ---
        if ($tape->movements()->count() === 0) {
            $service->recordMovement($wh1->id, $tape->id, 'receipt', 100, 3.50, 'PO-TAPE-01', now()->subDays(14)->toDateString(), null);
            $service->recordMovement($wh1->id, $tape->id, 'issue', -25, 0, 'OUT-OPS', now()->subDays(5)->toDateString(), null);
        }

        // --- Spare parts: receipt and one adjustment (count correction) ---
        if ($sparePart->movements()->count() === 0) {
            $service->recordMovement($wh1->id, $sparePart->id, 'receipt', 24, 28.00, 'PO-SP-01', now()->subDays(10)->toDateString(), 'Forklift filters');
            $service->recordMovement($wh1->id, $sparePart->id, 'issue', -4, 0, 'MNT-001', now()->subDays(2)->toDateString(), 'Maintenance use');
            $service->recordMovement($wh1->id, $sparePart->id, 'adjustment', -1, 0, null, now()->subDay()->toDateString(), 'Count correction');
        }
    }
}
