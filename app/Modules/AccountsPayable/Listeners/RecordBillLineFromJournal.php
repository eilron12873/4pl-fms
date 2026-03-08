<?php

namespace App\Modules\AccountsPayable\Listeners;

use App\Events\JournalPosted;
use App\Modules\AccountsPayable\Application\BillService;

class RecordBillLineFromJournal
{
    public function __construct(
        protected BillService $billService,
    ) {
    }

    public function handle(JournalPosted $event): void
    {
        $meta = $event->meta;
        if (($meta['event_type'] ?? null) !== 'vendor-invoice-approved') {
            return;
        }

        $payload = $meta['payload'] ?? [];
        $vendorId = $payload['vendor_id'] ?? null;
        if (! $vendorId) {
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

        $description = $journal->description ?? 'Vendor invoice ' . ($meta['source_reference'] ?? '');

        $this->billService->createBillLineFromJournal([
            'vendor_id' => $vendorId,
            'journal_id' => $journal->id,
            'source_reference' => $meta['source_reference'] ?? null,
            'source_type' => 'vendor_invoice',
            'amount' => $amount,
            'description' => $description,
            'bill_date' => $journal->journal_date?->toDateString() ?? now()->toDateString(),
        ]);
    }
}
