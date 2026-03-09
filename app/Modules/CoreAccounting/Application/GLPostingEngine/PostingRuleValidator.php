<?php

namespace App\Modules\CoreAccounting\Application\GLPostingEngine;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use InvalidArgumentException;

class PostingRuleValidator
{
    public function assertUsable(PostingRule $rule): void
    {
        if (! $rule->is_active) {
            throw new InvalidArgumentException("Posting rule {$rule->id} is not active.");
        }

        if ($rule->lines->count() < 2) {
            throw new InvalidArgumentException("Posting rule {$rule->id} must have at least two lines.");
        }

        $hasDebit = $rule->lines->contains(fn ($line) => $line->entry_type === 'debit');
        $hasCredit = $rule->lines->contains(fn ($line) => $line->entry_type === 'credit');

        if (! $hasDebit || ! $hasCredit) {
            throw new InvalidArgumentException("Posting rule {$rule->id} must contain at least one debit and one credit line.");
        }

        foreach ($rule->lines as $line) {
            if ($line->account === null) {
                throw new InvalidArgumentException("Posting rule line {$line->id} references a missing account.");
            }
        }
    }
}

