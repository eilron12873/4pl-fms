<?php

namespace App\Modules\BillingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'client_id',
        'service_type_id',
        'name',
        'contract_number',
        'effective_from',
        'effective_to',
        'status',
        'sla_terms',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(BillingClient::class, 'client_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function rateDefinitions(): HasMany
    {
        return $this->hasMany(ContractRateDefinition::class, 'contract_id');
    }

    public function slaPenaltyRules(): HasMany
    {
        return $this->hasMany(SlaPenaltyRule::class, 'contract_id');
    }

    public function isActiveOn(string $date): bool
    {
        $d = \Carbon\Carbon::parse($date)->startOfDay();
        if ($d->lt($this->effective_from->startOfDay())) {
            return false;
        }
        if ($this->effective_to && $d->gt($this->effective_to->endOfDay())) {
            return false;
        }
        return true;
    }
}
