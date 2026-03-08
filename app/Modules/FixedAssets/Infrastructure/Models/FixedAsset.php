<?php

namespace App\Modules\FixedAssets\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    protected $table = 'fixed_assets';

    public const TYPE_VEHICLE = 'vehicle';
    public const TYPE_EQUIPMENT = 'equipment';
    public const TYPE_IT = 'it';
    public const TYPE_BUILDING = 'building';
    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISPOSED = 'disposed';

    public const METHOD_STRAIGHT_LINE = 'straight_line';

    protected $fillable = [
        'code',
        'name',
        'asset_type',
        'purchase_date',
        'acquisition_cost',
        'useful_life_years',
        'residual_value',
        'depreciation_method',
        'gl_asset_code',
        'gl_accum_depn_code',
        'gl_depn_expense_code',
        'status',
        'location',
        'custodian',
        'notes',
        'accumulated_depreciation',
        'last_depreciation_at',
        'disposed_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'last_depreciation_at' => 'date',
        'disposed_at' => 'datetime',
    ];

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class, 'fixed_asset_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function depreciableAmount(): float
    {
        return (float) $this->acquisition_cost - (float) $this->residual_value;
    }

    public function bookValue(): float
    {
        return (float) $this->acquisition_cost - (float) $this->accumulated_depreciation;
    }
}
