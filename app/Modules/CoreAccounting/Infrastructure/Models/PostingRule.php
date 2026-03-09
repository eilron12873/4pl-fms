<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostingRule extends Model
{
    protected $fillable = [
        'event_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PostingRuleLine::class)->orderBy('sequence');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PostingRuleCondition::class);
    }
}

