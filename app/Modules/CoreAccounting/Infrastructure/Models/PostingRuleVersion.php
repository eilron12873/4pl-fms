<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingRuleVersion extends Model
{
    protected $fillable = [
        'posting_rule_id',
        'version_number',
        'status',
        'effective_from',
        'effective_to',
        'created_by',
        'reviewed_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
    ];

    public function postingRule(): BelongsTo
    {
        return $this->belongsTo(PostingRule::class);
    }
}

