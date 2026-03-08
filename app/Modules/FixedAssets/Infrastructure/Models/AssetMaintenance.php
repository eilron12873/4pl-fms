<?php

namespace App\Modules\FixedAssets\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $table = 'asset_maintenance';

    protected $fillable = [
        'fixed_asset_id',
        'maintenance_date',
        'amount',
        'description',
        'reference',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
