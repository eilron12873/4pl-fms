<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodCloseEvidence extends Model
{
    protected $table = 'period_close_evidences';

    protected $fillable = [
        'period_id',
        'created_by',
        'checks',
        'metadata',
    ];

    protected $casts = [
        'checks' => 'array',
        'metadata' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}

