<?php

namespace App\Modules\CoreAccounting\Application\GLPostingEngine;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use Illuminate\Support\Collection;

class PostingRuleResolver
{
    public function __construct(
        protected ConditionalRuleEngine $conditionalEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function findActiveRuleForEvent(string $eventType, array $payload): ?PostingRule
    {
        /** @var Collection<int, PostingRule> $rules */
        $rules = PostingRule::with(['lines', 'conditions'])
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return null;
        }

        return $this->conditionalEngine->selectRule($rules, $payload);
    }
}

