<?php

namespace App\Modules\InventoryValuation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $fillable = ['code', 'name', 'sku', 'unit', 'valuation_method', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'item_id');
    }
}
