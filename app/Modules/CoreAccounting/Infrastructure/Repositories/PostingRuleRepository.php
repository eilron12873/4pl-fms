<?php

namespace App\Modules\CoreAccounting\Infrastructure\Repositories;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use Illuminate\Database\Eloquent\Collection;

class PostingRuleRepository
{
    /**
     * @return Collection<int, PostingRule>
     */
    public function findActiveByEventType(string $eventType): Collection
    {
        return PostingRule::with(['lines', 'conditions'])
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();
    }
}

