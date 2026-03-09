<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodChangeLog extends Model
{
    protected $fillable = [
        'period_id',
        'action',
        'user_id',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}

