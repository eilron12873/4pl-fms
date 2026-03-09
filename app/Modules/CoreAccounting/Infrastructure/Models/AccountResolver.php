<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountResolver extends Model
{
    protected $fillable = [
        'resolver_type',
        'dimension_key',
        'dimension_value',
        'account_id',
        'priority',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

