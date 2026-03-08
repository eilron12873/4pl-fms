<?php

namespace App\Modules\BillingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaPenaltyRule extends Model
{
    protected $fillable = [
        'contract_id',
        'penalty_type',
        'amount_type',
        'amount',
        'conditions',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'conditions' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
