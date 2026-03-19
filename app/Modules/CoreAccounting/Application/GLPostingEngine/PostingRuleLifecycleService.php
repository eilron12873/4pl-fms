<?php

namespace App\Modules\CoreAccounting\Application\GLPostingEngine;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleVersion;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class PostingRuleLifecycleService
{
    /**
     * Allowed workflow: draft -> review -> approved -> active -> retired.
     */
    public function transition(PostingRuleVersion $version, string $nextStatus): PostingRuleVersion
    {
        $allowed = [
            'draft' => ['review'],
            'review' => ['approved', 'draft'],
            'approved' => ['active'],
            'active' => ['retired'],
            'retired' => [],
        ];

        $current = $version->status;
        if (! isset($allowed[$current]) || ! in_array($nextStatus, $allowed[$current], true)) {
            throw new InvalidArgumentException("Invalid posting-rule status transition: {$current} -> {$nextStatus}.");
        }

        $payload = ['status' => $nextStatus];
        if ($nextStatus === 'active' && ! $version->effective_from) {
            $payload['effective_from'] = Carbon::now()->toDateString();
        }
        if ($nextStatus === 'approved') {
            $payload['approved_at'] = Carbon::now();
        }

        $version->update($payload);

        return $version->refresh();
    }
}

