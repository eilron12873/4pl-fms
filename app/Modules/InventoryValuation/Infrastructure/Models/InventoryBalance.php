<?php

namespace App\Modules\InventoryValuation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBalance extends Model
{
    protected $table = 'inventory_balances';

    protected $fillable = ['warehouse_id', 'item_id', 'quantity', 'unit_cost', 'last_movement_at'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'last_movement_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function getValueAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_cost;
    }
}
