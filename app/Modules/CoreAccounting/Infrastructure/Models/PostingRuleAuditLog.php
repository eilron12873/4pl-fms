<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingRuleAuditLog extends Model
{
    protected $fillable = [
        'posting_rule_id',
        'posting_rule_version_id',
        'action',
        'actor_user_id',
        'before_state',
        'after_state',
        'reason',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
    ];

    public function postingRule(): BelongsTo
    {
        return $this->belongsTo(PostingRule::class);
    }

    public function postingRuleVersion(): BelongsTo
    {
        return $this->belongsTo(PostingRuleVersion::class);
    }
}

