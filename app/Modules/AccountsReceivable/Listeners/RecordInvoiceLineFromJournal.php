<?php

namespace App\Modules\AccountsReceivable\Listeners;

use App\Events\JournalPosted;
use App\Modules\AccountsReceivable\Application\InvoiceService;

class RecordInvoiceLineFromJournal
{
    protected static array $billableEventTypes = [
        'shipment-delivered',
        'storage-accrual',
        'project-milestone-completed',
    ];

    public function __construct(
        protected InvoiceService $invoiceService,
    ) {
    }

    public function handle(JournalPosted $event): void
    {
        $meta = $event->meta;
        $eventType = $meta['event_type'] ?? null;
        $payload = $meta['payload'] ?? [];

        if (! $eventType || ! in_array($eventType, self::$billableEventTypes, true)) {
            return;
        }

        $clientId = $payload['client_id'] ?? null;
        if (! $clientId) {
            return;
        }

        $journal = $event->journal;
        $amount = (float) $journal->lines->sum('debit');
        if ($amount <= 0) {
            $amount = (float) $journal->lines->sum('credit');
        }
        if ($amount <= 0) {
            return;
        }

        $description = $journal->description ?? $eventType . ' ' . ($meta['source_reference'] ?? '');

        $this->invoiceService->createInvoiceLineFromJournal([
            'client_id' => $clientId,
            'journal_id' => $journal->id,
            'source_reference' => $meta['source_reference'] ?? null,
            'source_type' => $eventType,
            'amount' => $amount,
            'description' => $description,
            'invoice_date' => $journal->journal_date?->toDateString() ?? now()->toDateString(),
            'shipment_id' => $payload['shipment_id'] ?? null,
        ]);
    }
}
