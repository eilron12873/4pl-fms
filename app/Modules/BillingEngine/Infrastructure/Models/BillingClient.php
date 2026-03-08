<?php

namespace App\Modules\BillingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingClient extends Model
{
    protected $fillable = [
        'external_id',
        'code',
        'name',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'client_id');
    }
}
