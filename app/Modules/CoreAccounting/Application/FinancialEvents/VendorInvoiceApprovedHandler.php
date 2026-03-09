<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

use App\Modules\CoreAccounting\Application\GLPostingEngine\GLPostingEngine;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Validation\ValidationException;

class VendorInvoiceApprovedHandler implements FinancialEventHandlerInterface
{
    public function __construct(
        protected JournalService $journalService,
        protected GLPostingEngine $glPostingEngine,
    ) {
    }

    public function supports(string $eventType): bool
    {
        return $eventType === 'vendor-invoice-approved';
    }

    public function handle(array $payload, array $context): ?Journal
    {
        $this->validate($payload);

        $eventType = 'vendor-invoice-approved';

        $lines = $this->glPostingEngine->buildJournal($eventType, $payload);

        if ($lines === null) {
            $amount = (float) $payload['amount'];
            $apCode = $payload['accounts_payable_account_code'] ?? '2100';
            $accruedCode = $payload['accrued_liability_account_code'] ?? null;
            $expenseCode = $payload['expense_account_code'] ?? '5200';

            if ($accruedCode) {
                $lines = [
                    ['account_code' => $accruedCode, 'debit' => $amount, 'credit' => 0],
                    ['account_code' => $apCode, 'debit' => 0, 'credit' => $amount],
                ];
            } else {
                $lines = [
                    ['account_code' => $expenseCode, 'debit' => $amount, 'credit' => 0],
                    ['account_code' => $apCode, 'debit' => 0, 'credit' => $amount],
                ];
            }
        }

        $journal = $this->journalService->post($lines, [
            'description' => $payload['description'] ?? 'Vendor invoice approved',
            'journal_date' => $payload['journal_date'] ?? now()->toDateString(),
            'source_system' => $context['source_system'],
            'source_type' => $payload['source_type'] ?? 'vendor_invoice',
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
                'payload' => ['For vendor-invoice-approved, payload.amount is required.'],
            ]);
        }
    }
}
