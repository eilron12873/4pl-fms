<?php

namespace App\Modules\InventoryValuation\Application;

use App\Modules\InventoryValuation\Infrastructure\Models\InventoryBalance;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryMovement;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Support\Collection;

class InventoryValuationService
{
    public function recordMovement(
        int $warehouseId,
        int $itemId,
        string $movementType,
        float $quantity,
        float $unitCost = 0,
        ?string $reference = null,
        ?string $movementDate = null,
        ?string $notes = null,
    ): InventoryMovement {
        $movementDate = $movementDate ?? now()->toDateString();
        $movement = InventoryMovement::create([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference' => $reference,
            'movement_date' => $movementDate,
            'notes' => $notes,
        ]);

        $this->updateBalanceFromMovement($movement);
        return $movement;
    }

    protected function updateBalanceFromMovement(InventoryMovement $movement): void
    {
        $balance = InventoryBalance::firstOrCreate(
            [
                'warehouse_id' => $movement->warehouse_id,
                'item_id' => $movement->item_id,
            ],
            ['quantity' => 0, 'unit_cost' => 0]
        );

        $qty = (float) $balance->quantity;
        $cost = (float) $balance->unit_cost;
        $movQty = (float) $movement->quantity;
        $movCost = (float) $movement->unit_cost;

        if ($movement->isInbound()) {
            if ($qty + $movQty <= 0) {
                $newCost = 0;
            } else {
                $newCost = (($qty * $cost) + ($movQty * $movCost)) / ($qty + $movQty);
            }
            $balance->quantity = $qty + $movQty;
            $balance->unit_cost = round($newCost, 4);
        } else {
            $balance->quantity = $qty + $movQty;
            if ($balance->quantity <= 0) {
                $balance->unit_cost = 0;
            }
        }

        $balance->last_movement_at = now();
        $balance->save();
    }

    /**
     * @return Collection<int, array{warehouse_id: int, warehouse_code: string, item_id: int, item_code: string, quantity: float, unit_cost: float, value: float}>
     */
    public function valuationReport(?int $warehouseId = null, ?int $itemId = null): Collection
    {
        $query = InventoryBalance::with(['warehouse', 'item'])
            ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
            ->whereHas('item', fn ($q) => $q->where('is_active', true));

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        return $query->get()->map(fn (InventoryBalance $b) => [
            'warehouse_id' => $b->warehouse_id,
            'warehouse_code' => $b->warehouse->code ?? '',
            'warehouse_name' => $b->warehouse->name ?? '',
            'item_id' => $b->item_id,
            'item_code' => $b->item->code ?? '',
            'item_name' => $b->item->name ?? '',
            'quantity' => (float) $b->quantity,
            'unit_cost' => (float) $b->unit_cost,
            'value' => (float) $b->value,
        ]);
    }

    public function totalValuation(?int $warehouseId = null): float
    {
        $rows = $this->valuationReport($warehouseId, null);
        return round($rows->sum('value'), 2);
    }
}
