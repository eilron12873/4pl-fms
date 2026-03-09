<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingRuleLine extends Model
{
    protected $fillable = [
        'posting_rule_id',
        'account_id',
        'resolver_type',
        'entry_type',
        'amount_source',
        'dimension_source',
        'sequence',
    ];

    protected $casts = [
        'dimension_source' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PostingRule::class, 'posting_rule_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

