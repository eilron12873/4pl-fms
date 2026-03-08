<?php

namespace App\Modules\BillingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRateDefinition extends Model
{
    protected $fillable = [
        'contract_id',
        'rate_type',
        'unit_price',
        'currency',
        'min_quantity',
        'max_quantity',
        'effective_from',
        'effective_to',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function appliesToQuantity(float $quantity): bool
    {
        if ($this->min_quantity !== null && $quantity < (float) $this->min_quantity) {
            return false;
        }
        if ($this->max_quantity !== null && $quantity > (float) $this->max_quantity) {
            return false;
        }
        return true;
    }

    public function isEffectiveOn(string $date): bool
    {
        $d = \Carbon\Carbon::parse($date)->startOfDay();
        if ($this->effective_from && $d->lt($this->effective_from->startOfDay())) {
            return false;
        }
        if ($this->effective_to && $d->gt($this->effective_to->endOfDay())) {
            return false;
        }
        return true;
    }
}
