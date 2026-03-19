<?php

namespace App\Modules\InventoryValuation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryFifoLayer extends Model
{
    protected $table = 'inventory_fifo_layers';

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity_original',
        'quantity_remaining',
        'unit_cost',
        'layer_date',
        'reference',
        'source_movement_id',
        'source_movement_type',
    ];

    protected $casts = [
        'quantity_original' => 'decimal:4',
        'quantity_remaining' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'layer_date' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}

