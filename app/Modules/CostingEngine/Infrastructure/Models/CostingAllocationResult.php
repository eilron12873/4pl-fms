<?php

namespace App\Modules\CostingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostingAllocationResult extends Model
{
    protected $table = 'costing_allocation_results';

    protected $fillable = [
        'rule_id',
        'allocation_date',
        'target_dimension',
        'target_id',
        'allocated_amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'allocated_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CostingAllocationRule::class, 'rule_id');
    }
}

