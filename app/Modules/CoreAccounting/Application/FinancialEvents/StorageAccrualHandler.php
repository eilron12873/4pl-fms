<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

use App\Modules\CoreAccounting\Application\GLPostingEngine\GLPostingEngine;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Validation\ValidationException;

class StorageAccrualHandler implements FinancialEventHandlerInterface
{
    public function __construct(
        protected JournalService $journalService,
        protected GLPostingEngine $glPostingEngine,
    ) {
    }

    public function supports(string $eventType): bool
    {
        return $eventType === 'storage-accrual';
    }

    public function handle(array $payload, array $context): ?Journal
    {
        $this->validate($payload);

        $eventType = 'storage-accrual';

        $lines = $this->glPostingEngine->buildJournal($eventType, $payload);

        if ($lines === null) {
            $amount = (float) $payload['amount'];
            $storageExpenseCode = $payload['storage_expense_account_code'] ?? '5100';
            $accruedLiabilityCode = $payload['accrued_liability_account_code'] ?? '2100';

            $lines = [
                [
                    'account_code' => $storageExpenseCode,
                    'debit' => $amount,
                    'credit' => 0,
                    'client_id' => $payload['client_id'] ?? null,
                    'warehouse_id' => $payload['warehouse_id'] ?? null,
                ],
                [
                    'account_code' => $accruedLiabilityCode,
                    'debit' => 0,
                    'credit' => $amount,
                    'client_id' => $payload['client_id'] ?? null,
                    'warehouse_id' => $payload['warehouse_id'] ?? null,
                ],
            ];
        }

        $journal = $this->journalService->post($lines, [
            'description' => $payload['description'] ?? 'Storage accrual',
            'journal_date' => $payload['journal_date'] ?? now()->toDateString(),
            'source_system' => $context['source_system'],
            'source_type' => $payload['source_type'] ?? 'storage',
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
                'payload' => ['For storage-accrual, payload.amount is required.'],
            ]);
        }
    }
}
