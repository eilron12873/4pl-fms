<?php

namespace App\Modules\CoreAccounting\Application\GLPostingEngine;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use Illuminate\Support\Collection;

class ConditionalRuleEngine
{
    /**
     * @param  Collection<int, PostingRule>  $rules
     * @param  array<string, mixed>  $payload
     */
    public function selectRule(Collection $rules, array $payload): ?PostingRule
    {
        if ($rules->isEmpty()) {
            return null;
        }

        // First, try to find a rule where all conditions match the payload.
        $matching = $rules->filter(function (PostingRule $rule) use ($payload) {
            if ($rule->conditions->isEmpty()) {
                return false;
            }

            foreach ($rule->conditions as $condition) {
                $field = $condition->field_name;
                $operator = $condition->operator;
                $value = $condition->comparison_value;

                if (! array_key_exists($field, $payload)) {
                    return false;
                }

                $actual = $payload[$field];

                if (! $this->evaluate($actual, $operator, $value)) {
                    return false;
                }
            }

            return true;
        });

        if ($matching->isNotEmpty()) {
            return $matching->sortBy('id')->first();
        }

        // If no conditional rule matches, fall back to the first active rule
        // to preserve existing behaviour.
        return $rules->sortBy('id')->first();
    }

    /**
     * @param  mixed  $actual
     * @param  string  $operator
     * @param  string  $value
     */
    protected function evaluate($actual, string $operator, string $value): bool
    {
        switch (strtoupper($operator)) {
            case '=':
            case '==':
                return (string) $actual === $value;
            case '!=':
                return (string) $actual !== $value;
            case '>':
                return (float) $actual > (float) $value;
            case '<':
                return (float) $actual < (float) $value;
            case 'IN':
                $set = array_map('trim', explode(',', $value));
                return in_array((string) $actual, $set, true);
            case 'NOT IN':
                $set = array_map('trim', explode(',', $value));
                return ! in_array((string) $actual, $set, true);
            default:
                return false;
        }
    }
}

