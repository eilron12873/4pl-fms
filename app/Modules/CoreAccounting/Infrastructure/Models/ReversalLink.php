<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversalLink extends Model
{
    protected $fillable = [
        'original_journal_id',
        'reversal_journal_id',
    ];

    public function original(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'original_journal_id');
    }

    public function reversal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversal_journal_id');
    }
}

