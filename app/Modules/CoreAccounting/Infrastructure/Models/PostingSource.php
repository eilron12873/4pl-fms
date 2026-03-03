<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingSource extends Model
{
    protected $fillable = [
        'journal_id',
        'source_system',
        'source_type',
        'source_reference',
        'event_type',
        'idempotency_key',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}

