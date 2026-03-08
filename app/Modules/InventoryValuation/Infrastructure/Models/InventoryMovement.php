<?php

namespace App\Modules\InventoryValuation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'reference',
        'movement_date',
        'journal_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'movement_date' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function isInbound(): bool
    {
        return in_array($this->movement_type, ['receipt', 'transfer_in', 'adjustment'], true);
    }
}
