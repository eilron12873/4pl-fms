<?php

namespace App\Modules\CostingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class CostingProfitabilitySnapshot extends Model
{
    protected $table = 'costing_profitability_snapshots';

    protected $fillable = [
        'dimension',
        'dimension_id',
        'from_date',
        'to_date',
        'revenue',
        'cost',
        'margin',
        'margin_pct',
        'currency',
        'computed_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'revenue' => 'decimal:2',
        'cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_pct' => 'decimal:2',
        'computed_at' => 'datetime',
    ];
}

