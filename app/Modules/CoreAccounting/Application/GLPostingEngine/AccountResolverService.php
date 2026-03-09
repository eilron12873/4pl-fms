<?php

namespace App\Modules\CoreAccounting\Application\GLPostingEngine;

use App\Modules\CoreAccounting\Infrastructure\Models\AccountResolver;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleLine;

class AccountResolverService
{
    /**
     * Resolve the effective account_id for a posting rule line.
     *
     * When no resolver_type is configured or no matching resolver is found,
     * the line's own account_id is returned unchanged (to avoid regression).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $dimensions
     */
    public function resolveAccountId(PostingRuleLine $line, array $payload, array $dimensions): int
    {
        if (! $line->resolver_type) {
            return (int) $line->account_id;
        }

        $candidates = AccountResolver::where('resolver_type', $line->resolver_type)
            ->orderBy('priority')
            ->get();

        if ($candidates->isEmpty()) {
            return (int) $line->account_id;
        }

        $context = array_merge($payload, $dimensions);

        foreach ($candidates as $resolver) {
            $key = $resolver->dimension_key;

            if (! array_key_exists($key, $context)) {
                continue;
            }

            if ((string) $context[$key] === $resolver->dimension_value) {
                return (int) $resolver->account_id;
            }
        }

        return (int) $line->account_id;
    }
}

