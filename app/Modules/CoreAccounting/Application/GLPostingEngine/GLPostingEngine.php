<?php

namespace App\Modules\CoreAccounting\Application\GLPostingEngine;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleLine;
use InvalidArgumentException;

class GLPostingEngine
{
    public function __construct(
        protected PostingRuleResolver $resolver,
        protected PostingRuleValidator $validator,
        protected AccountResolverService $accountResolver,
    ) {
    }

    /**
     * Build journal lines for the given event and payload.
     *
     * Returns null when no active rule exists, so callers can safely fall back
     * to existing hardcoded logic (to avoid regression).
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>|null
     */
    public function buildJournal(string $eventType, array $payload): ?array
    {
        $rule = $this->resolver->findActiveRuleForEvent($eventType, $payload);

        if (! $rule) {
            return null;
        }

        $this->validator->assertUsable($rule);

        $lines = [];

        /** @var PostingRuleLine $line */
        foreach ($rule->lines as $line) {
            $amount = $this->resolveAmount($line, $payload);

            if ($amount === 0.0) {
                continue;
            }

            [$debit, $credit] = $line->entry_type === 'debit'
                ? [$amount, 0.0]
                : [0.0, $amount];

            $dimensions = $this->resolveDimensions($line, $payload);

            $accountId = $this->accountResolver->resolveAccountId($line, $payload, $dimensions);

            $lines[] = array_merge([
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
            ], $dimensions);
        }

        if (empty($lines)) {
            throw new InvalidArgumentException("Posting rule {$rule->id} produced no lines for event {$eventType}.");
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveAmount(PostingRuleLine $line, array $payload): float
    {
        $source = $line->amount_source;

        if (! array_key_exists($source, $payload)) {
            throw new InvalidArgumentException("Amount source '{$source}' not found in payload.");
        }

        return (float) $payload[$source];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function resolveDimensions(PostingRuleLine $line, array $payload): array
    {
        $dimensionSource = $line->dimension_source ?? [];

        if (! is_array($dimensionSource)) {
            return [];
        }

        $dimensions = [];

        foreach ($dimensionSource as $dimensionKey => $expression) {
            if (! is_string($expression)) {
                continue;
            }

            if (str_starts_with($expression, 'payload.')) {
                $payloadKey = substr($expression, strlen('payload.'));

                if (array_key_exists($payloadKey, $payload)) {
                    $dimensions[$dimensionKey] = $payload[$payloadKey];
                }
            }
        }

        return $dimensions;
    }
}

