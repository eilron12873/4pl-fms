<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

use App\Modules\CoreAccounting\Application\GLPostingEngine\GLPostingEngine;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Validation\ValidationException;

abstract class AbstractRuleBasedEventHandler implements FinancialEventHandlerInterface
{
    public function __construct(
        protected JournalService $journalService,
        protected GLPostingEngine $glPostingEngine,
    ) {
    }

    abstract protected function eventType(): string;

    protected function defaultDescription(): string
    {
        return $this->eventType();
    }

    protected function defaultSourceType(): ?string
    {
        return null;
    }

    public function supports(string $eventType): bool
    {
        return $eventType === $this->eventType();
    }

    public function handle(array $payload, array $context): ?Journal
    {
        $lines = $this->glPostingEngine->buildJournal($this->eventType(), $payload);

        if ($lines === null) {
            throw ValidationException::withMessages([
                'payload' => ["No active posting rule configured for {$this->eventType()}."],
            ]);
        }

        $meta = [
            'description' => $payload['description'] ?? $this->defaultDescription(),
            'journal_date' => $payload['journal_date'] ?? now()->toDateString(),
            'source_system' => $context['source_system'],
            'source_type' => $payload['source_type'] ?? $this->defaultSourceType(),
            'source_reference' => $context['source_reference'],
            'event_type' => $this->eventType(),
            'idempotency_key' => $context['idempotency_key'],
            'payload' => $payload,
        ];

        return $this->journalService->post($lines, $meta);
    }
}

