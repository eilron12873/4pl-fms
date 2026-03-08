<?php

namespace App\Modules\InventoryValuation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = ['code', 'name', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'warehouse_id');
    }
}
