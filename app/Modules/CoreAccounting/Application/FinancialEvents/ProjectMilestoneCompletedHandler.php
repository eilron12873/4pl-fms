<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

use App\Modules\CoreAccounting\Application\GLPostingEngine\GLPostingEngine;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Validation\ValidationException;

class ProjectMilestoneCompletedHandler implements FinancialEventHandlerInterface
{
    public function __construct(
        protected JournalService $journalService,
        protected GLPostingEngine $glPostingEngine,
    ) {
    }

    public function supports(string $eventType): bool
    {
        return $eventType === 'project-milestone-completed';
    }

    public function handle(array $payload, array $context): ?Journal
    {
        $this->validate($payload);

        $eventType = 'project-milestone-completed';

        $lines = $this->glPostingEngine->buildJournal($eventType, $payload);

        if ($lines === null) {
            $amount = (float) $payload['amount'];
            $receivableCode = $payload['receivable_account_code'] ?? '1200';
            $revenueCode = $payload['revenue_account_code'] ?? '4100';

            $lines = [
                [
                    'account_code' => $receivableCode,
                    'debit' => $amount,
                    'credit' => 0,
                    'project_id' => $payload['project_id'] ?? null,
                    'client_id' => $payload['client_id'] ?? null,
                ],
                [
                    'account_code' => $revenueCode,
                    'debit' => 0,
                    'credit' => $amount,
                    'project_id' => $payload['project_id'] ?? null,
                    'client_id' => $payload['client_id'] ?? null,
                ],
            ];
        }

        $journal = $this->journalService->post($lines, [
            'description' => $payload['description'] ?? 'Project milestone completed',
            'journal_date' => $payload['journal_date'] ?? now()->toDateString(),
            'source_system' => $context['source_system'],
            'source_type' => $payload['source_type'] ?? 'project_milestone',
            'source_reference' => $context['source_reference'],
            'event_type' => $eventType,
            'idempotency_key' => $context['idempotency_key'],
            'payload' => $payload,
        ]);

        return $journal;
    }

    /** @param  array<string, mixed>  $payload */
    protected function validate(array $payload): void
    {
        if (! isset($payload['amount'])) {
            throw ValidationException::withMessages([
                'payload' => ['For project-milestone-completed, payload.amount is required.'],
            ]);
        }
    }
}
