<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingRuleCondition extends Model
{
    protected $fillable = [
        'posting_rule_id',
        'field_name',
        'operator',
        'comparison_value',
        'priority',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PostingRule::class, 'posting_rule_id');
    }
}

