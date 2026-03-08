<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Validation\ValidationException;

class ShipmentDeliveredHandler implements FinancialEventHandlerInterface
{
    public function __construct(
        protected JournalService $journalService,
    ) {
    }

    public function supports(string $eventType): bool
    {
        return $eventType === 'shipment-delivered';
    }

    public function handle(array $payload, array $context): ?Journal
    {
        $this->validate($payload);

        $amount = (float) $payload['amount'];
        $lines = [
            [
                'account_code' => $payload['receivable_account_code'],
                'debit' => $amount,
                'credit' => 0,
                'shipment_id' => $payload['shipment_id'] ?? null,
                'client_id' => $payload['client_id'] ?? null,
                'route_id' => $payload['route_id'] ?? null,
            ],
            [
                'account_code' => $payload['revenue_account_code'],
                'debit' => 0,
                'credit' => $amount,
                'shipment_id' => $payload['shipment_id'] ?? null,
                'client_id' => $payload['client_id'] ?? null,
                'route_id' => $payload['route_id'] ?? null,
            ],
        ];

        $journal = $this->journalService->post($lines, [
            'description' => $payload['description'] ?? 'Shipment delivered',
            'journal_date' => $payload['journal_date'] ?? now()->toDateString(),
            'source_system' => $context['source_system'],
            'source_type' => $payload['source_type'] ?? 'shipment',
            'source_reference' => $context['source_reference'],
            'event_type' => 'shipment-delivered',
            'idempotency_key' => $context['idempotency_key'],
            'payload' => $payload,
        ]);

        return $journal;
    }

    /** @param  array<string, mixed>  $payload */
    protected function validate(array $payload): void
    {
        $required = ['amount', 'revenue_account_code', 'receivable_account_code'];
        foreach ($required as $key) {
            if (! isset($payload[$key])) {
                throw ValidationException::withMessages([
                    'payload' => ["For shipment-delivered, payload.{$key} is required."],
                ]);
            }
        }
    }
}
