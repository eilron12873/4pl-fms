<?php

namespace App\Modules\InventoryValuation\Application;

use App\Modules\InventoryValuation\Infrastructure\Models\InventoryFifoLayer;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryBalance;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class InventoryValuationService
{
    /**
     * Get the balance row for update (or create it if missing).
     * This prevents race conditions around firstOrCreate under concurrent movements.
     */
    protected function balanceForUpdateOrCreate(int $warehouseId, int $itemId): InventoryBalance
    {
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        try {
            return InventoryBalance::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'quantity' => 0,
                'unit_cost' => 0,
                'last_movement_at' => now(),
            ]);
        } catch (QueryException) {
            // Another concurrent transaction likely created the row first.
            return InventoryBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }

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

        return DB::transaction(function () use (
            $warehouseId,
            $itemId,
            $movementType,
            $quantity,
            $unitCost,
            $reference,
            $movementDate,
            $notes
        ) {
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

            $item = InventoryItem::findOrFail($itemId);
            $valuationMethod = (string) ($item->valuation_method ?? 'weighted_avg');

            $this->updateBalanceFromMovement($movement, $valuationMethod);

            return $movement;
        });
    }

    protected function updateBalanceFromMovement(InventoryMovement $movement, string $valuationMethod): void
    {
        if ($valuationMethod === 'fifo') {
            $this->updateFifoBalanceFromMovement($movement);
            return;
        }

        $this->updateWeightedAvgBalanceFromMovement($movement);
    }

    protected function updateWeightedAvgBalanceFromMovement(InventoryMovement $movement): void
    {
        $balance = $this->balanceForUpdateOrCreate((int) $movement->warehouse_id, (int) $movement->item_id);

        $qty = (float) $balance->quantity;
        $cost = (float) $balance->unit_cost;
        $movQty = (float) $movement->quantity;
        $movCost = (float) $movement->unit_cost;

        if ($movement->isInbound()) {
            if ($movQty <= 0) {
                throw new InvalidArgumentException('Inbound movements require positive quantity.');
            }

            $newQty = $qty + $movQty;
            if ($newQty < 0) {
                throw new InvalidArgumentException('Inventory balance cannot go negative.');
            }

            if ($newQty <= 0) {
                $balance->quantity = 0;
                $balance->unit_cost = 0;
            } else {
                $newCost = (($qty * $cost) + ($movQty * $movCost)) / $newQty;
                $balance->quantity = $newQty;
                $balance->unit_cost = round($newCost, 4);
            }
        } else {
            if ($movQty >= 0) {
                throw new InvalidArgumentException('Outbound movements require negative quantity.');
            }

            $newQty = $qty + $movQty; // movQty negative
            if ($newQty < 0) {
                throw new InvalidArgumentException('Insufficient inventory on hand for movement.');
            }

            $balance->quantity = $newQty;
            if ($balance->quantity == 0) {
                $balance->unit_cost = 0;
            }
        }

        $balance->last_movement_at = now();
        $balance->save();
    }

    protected function updateFifoBalanceFromMovement(InventoryMovement $movement): void
    {
        $balance = $this->balanceForUpdateOrCreate((int) $movement->warehouse_id, (int) $movement->item_id);

        $qty = (float) $balance->quantity;
        $cost = (float) $balance->unit_cost;
        $movQty = (float) $movement->quantity;
        $movCost = (float) $movement->unit_cost;

        // Signed convention (enforced later in invariants):
        // - inbound movement types create/consume positive quantity
        // - outbound movement types reduce on-hand via negative quantity
        if ($movement->isInbound()) {
            if ($movQty <= 0) {
                throw new InvalidArgumentException('FIFO inbound movements require positive quantity.');
            }

            InventoryFifoLayer::create([
                'warehouse_id' => $movement->warehouse_id,
                'item_id' => $movement->item_id,
                'quantity_original' => $movQty,
                'quantity_remaining' => $movQty,
                'unit_cost' => round($movCost, 4),
                'layer_date' => method_exists($movement->movement_date, 'toDateString')
                    ? $movement->movement_date->toDateString()
                    : (string) $movement->movement_date,
                'reference' => $movement->reference,
                'source_movement_id' => $movement->id,
                'source_movement_type' => $movement->movement_type,
            ]);

            $balance->quantity = $qty + $movQty;
            $newValue = ($qty * $cost) + ($movQty * $movCost);
            $balance->unit_cost = $balance->quantity > 0 ? round($newValue / $balance->quantity, 4) : 0;
        } else {
            if ($movQty >= 0) {
                throw new InvalidArgumentException('Outbound movements require negative quantity.');
            }

            $outQty = abs($movQty);
            if ($outQty <= 0) {
                throw new InvalidArgumentException('FIFO outbound movements require non-zero quantity.');
            }

            if ($outQty > $qty) {
                throw new InvalidArgumentException('Insufficient inventory on hand for FIFO consumption.');
            }

            $remainingToConsume = $outQty;
            $consumedValue = 0.0;

            // Consume the oldest remaining layers first.
            $layers = InventoryFifoLayer::query()
                ->where('warehouse_id', $movement->warehouse_id)
                ->where('item_id', $movement->item_id)
                ->where('quantity_remaining', '>', 0)
                ->orderBy('layer_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($layers as $layer) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                $layerQty = (float) $layer->quantity_remaining;
                if ($layerQty <= 0) {
                    continue;
                }

                $takeQty = min($layerQty, $remainingToConsume);
                $takeQty = round($takeQty, 4);

                $layer->quantity_remaining = round($layerQty - $takeQty, 4);
                if ((float) $layer->quantity_remaining <= 0) {
                    $layer->quantity_remaining = 0;
                }
                $layer->save();

                $consumedValue += $takeQty * (float) $layer->unit_cost;
                $remainingToConsume -= $takeQty;
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new InvalidArgumentException('FIFO consumption could not be fully satisfied.');
            }

            // Compute remaining quantity/value from the layers after consumption.
            // This avoids rounding drift from `inventory_balances.unit_cost` when used as a source of truth.
            $remainingQty = 0.0;
            $remainingValue = 0.0;
            foreach ($layers as $layer) {
                $layerQtyRemaining = (float) $layer->quantity_remaining;
                if ($layerQtyRemaining <= 0) {
                    continue;
                }
                $remainingQty += $layerQtyRemaining;
                $remainingValue += $layerQtyRemaining * (float) $layer->unit_cost;
            }

            $balance->quantity = round($remainingQty, 4);
            if ($balance->quantity <= 0) {
                $balance->quantity = 0;
                $balance->unit_cost = 0;
            } else {
                $balance->unit_cost = round($remainingValue / $balance->quantity, 4);
            }

            // Store effective unit cost used for valuation (helps movement audit trail / UI).
            $movement->unit_cost = $outQty > 0 ? round($consumedValue / $outQty, 4) : 0;
            $movement->save();
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
            ->whereHas('item', fn ($q) => $q->where('is_active', true))
            ->orderBy('warehouse_id')
            ->orderBy('item_id');

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
            // Round at the report boundary to keep UI totals consistent.
            'value' => (float) ($b->quantity > 0 ? round((float) $b->quantity * (float) $b->unit_cost, 2) : 0),
        ]);
    }

    public function totalValuation(?int $warehouseId = null): float
    {
        $rows = $this->valuationReport($warehouseId, null);
        return round($rows->sum('value'), 2);
    }
}
