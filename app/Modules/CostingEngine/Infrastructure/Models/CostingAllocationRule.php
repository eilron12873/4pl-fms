<?php

namespace App\Modules\CostingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostingAllocationRule extends Model
{
    protected $table = 'costing_allocation_rules';

    protected $fillable = [
        'name',
        'rule_type',
        'target_dimension',
        'source_dimension',
        'fixed_amount',
        'percentage',
        'meta',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percentage' => 'decimal:4',
        'meta' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(CostingAllocationResult::class, 'rule_id');
    }
}

