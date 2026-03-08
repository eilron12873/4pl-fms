<?php

namespace App\Modules\AccountsReceivable\Application;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceLine;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoicePayment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceAdjustment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArPayment;
use App\Modules\BillingEngine\Application\RatingService;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected RatingService $ratingService,
        protected JournalService $journalService,
    ) {
    }

    /**
     * After a journal is posted for a billable event, record an invoice line linked to that journal.
     * Finds or creates an open (draft) invoice for the client and period, adds the line, recalc totals.
     *
     * @param  array{client_id: int, journal_id: int, source_reference: string, source_type: string, amount: float, description: string, invoice_date: string, shipment_id?: int}  $context
     */
    public function createInvoiceLineFromJournal(array $context): ArInvoiceLine
    {
        return DB::transaction(function () use ($context) {
            $clientId = (int) $context['client_id'];
            $journalId = (int) $context['journal_id'];
            $amount = (float) $context['amount'];
            $invoiceDate = $context['invoice_date'] ?? now()->toDateString();
            $description = $context['description'] ?? 'Invoice line';

            $invoice = ArInvoice::where('client_id', $clientId)
                ->where('status', 'draft')
                ->whereDate('invoice_date', '>=', now()->startOfMonth()->toDateString())
                ->whereDate('invoice_date', '<=', now()->endOfMonth()->toDateString())
                ->first();

            if (! $invoice) {
                $invoice = ArInvoice::create([
                    'client_id' => $clientId,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'invoice_date' => $invoiceDate,
                    'due_date' => now()->addDays(30)->toDateString(),
                    'status' => 'draft',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                    'currency' => \App\Modules\BillingEngine\Infrastructure\Models\BillingClient::find($clientId)?->currency ?? 'USD',
                ]);
            }

            $line = ArInvoiceLine::create([
                'invoice_id' => $invoice->id,
                'journal_id' => $journalId,
                'source_type' => $context['source_type'] ?? null,
                'source_reference' => $context['source_reference'] ?? null,
                'description' => $description,
                'quantity' => 1,
                'unit_price' => $amount,
                'amount' => $amount,
                'shipment_id' => $context['shipment_id'] ?? null,
                'client_id' => $clientId,
            ]);

            $this->recalculateInvoiceTotals($invoice);
            return $line;
        });
    }

    /**
     * Create a draft invoice with manual line items (no rating). Use for AR Entry.
     *
     * @param  array{client_id: int, invoice_date: string, due_date?: string, currency?: string, notes?: string, lines: array<array{description: string, amount: float}>}  $input
     */
    public function createManualInvoice(array $input): ArInvoice
    {
        return DB::transaction(function () use ($input) {
            $clientId = (int) $input['client_id'];
            $client = \App\Modules\BillingEngine\Infrastructure\Models\BillingClient::findOrFail($clientId);
            $invoiceDate = $input['invoice_date'] ?? now()->toDateString();
            $dueDate = $input['due_date'] ?? now()->addDays(30)->toDateString();
            $currency = $input['currency'] ?? $client->currency;
            $lines = $input['lines'] ?? [];
            if (empty($lines)) {
                throw new \InvalidArgumentException('At least one line is required.');
            }

            $invoice = ArInvoice::create([
                'client_id' => $clientId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => 'draft',
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'currency' => $currency,
                'notes' => $input['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $amount = (float) ($line['amount'] ?? 0);
                $description = $line['description'] ?? '';
                if ($amount <= 0 && $description === '') {
                    continue;
                }
                ArInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'description' => $description ?: 'Line',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'client_id' => $clientId,
                ]);
            }

            $this->recalculateInvoiceTotals($invoice);
            return $invoice->fresh();
        });
    }

    /**
     * Update a draft invoice (header and lines). Only allowed when status is draft.
     *
     * @param  array{client_id?: int, invoice_date: string, due_date?: string, currency?: string, notes?: string, lines: array<array{description: string, amount: float}>}  $input
     */
    public function updateDraftInvoice(ArInvoice $invoice, array $input): ArInvoice
    {
        if ($invoice->isIssued()) {
            throw new \InvalidArgumentException('Cannot edit an issued invoice.');
        }

        return DB::transaction(function () use ($invoice, $input) {
            $invoice->update([
                'invoice_date' => $input['invoice_date'] ?? $invoice->invoice_date->toDateString(),
                'due_date' => $input['due_date'] ?? $invoice->due_date->toDateString(),
                'currency' => $input['currency'] ?? $invoice->currency,
                'notes' => $input['notes'] ?? $invoice->notes,
            ]);
            if (isset($input['client_id'])) {
                $invoice->update(['client_id' => (int) $input['client_id']]);
            }

            $invoice->lines()->delete();
            $lines = $input['lines'] ?? [];
            $clientId = (int) $invoice->client_id;
            foreach ($lines as $line) {
                $amount = (float) ($line['amount'] ?? 0);
                $description = $line['description'] ?? '';
                if ($amount <= 0 && $description === '') {
                    continue;
                }
                ArInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'description' => $description ?: 'Line',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'client_id' => $clientId,
                ]);
            }

            $this->recalculateInvoiceTotals($invoice->fresh());
            return $invoice->fresh();
        });
    }

    /**
     * Create invoice with lines from BillingEngine rating (e.g. manual or batch).
     *
     * @param  array{client_id: int, invoice_date: string, due_date?: string, event_type: string, payload: array}  $input
     */
    public function createInvoiceFromBilling(array $input): ArInvoice
    {
        return DB::transaction(function () use ($input) {
            $clientId = (int) $input['client_id'];
            $payload = array_merge($input['payload'] ?? [], ['client_id' => $clientId]);
            $lines = $this->ratingService->rate($input['event_type'], $payload);
            if (empty($lines)) {
                throw new \InvalidArgumentException('No billable lines from rating.');
            }

            $client = \App\Modules\BillingEngine\Infrastructure\Models\BillingClient::findOrFail($clientId);
            $invoiceDate = $input['invoice_date'] ?? now()->toDateString();
            $dueDate = $input['due_date'] ?? now()->addDays(30)->toDateString();

            $invoice = ArInvoice::create([
                'client_id' => $clientId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => 'draft',
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'currency' => $client->currency,
            ]);

            $total = 0;
            foreach ($lines as $line) {
                ArInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'amount' => $line['amount'],
                    'source_type' => $input['event_type'] ?? null,
                ]);
                $total += $line['amount'];
            }

            $invoice->update(['subtotal' => $total, 'total' => $total]);
            return $invoice->fresh();
        });
    }

    /**
     * Issue invoice: optionally post journal (DR AR, CR Revenue) and mark status issued.
     */
    public function issueInvoice(ArInvoice $invoice, array $accountCodes = []): void
    {
        if ($invoice->isIssued()) {
            return;
        }
        $receivableCode = $accountCodes['receivable'] ?? '1100';
        $revenueCode = $accountCodes['revenue'] ?? '4100';
        $total = (float) $invoice->total;

        DB::transaction(function () use ($invoice, $receivableCode, $revenueCode, $total) {
            if (! $invoice->journal_id) {
                $journal = $this->journalService->post(
                    [
                        ['account_code' => $receivableCode, 'debit' => $total, 'credit' => 0, 'client_id' => $invoice->client_id],
                        ['account_code' => $revenueCode, 'debit' => 0, 'credit' => $total, 'client_id' => $invoice->client_id],
                    ],
                    [
                        'description' => 'AR Invoice ' . $invoice->invoice_number,
                        'journal_date' => $invoice->invoice_date->toDateString(),
                        'journal_number' => 'AR-INV-' . $invoice->invoice_number,
                    ],
                );
                $invoice->update(['journal_id' => $journal->id]);
            }
            $invoice->update(['status' => 'issued']);
        });
    }

    /**
     * Record payment and allocate to invoice(s).
     *
     * @param  array{client_id: int, payment_date: string, amount: float, currency: string, reference?: string, allocations: array<array{invoice_id: int, amount: float}>}  $input
     */
    public function recordPayment(array $input): ArPayment
    {
        return DB::transaction(function () use ($input) {
            $payment = ArPayment::create([
                'client_id' => $input['client_id'],
                'payment_date' => $input['payment_date'],
                'amount' => $input['amount'],
                'currency' => $input['currency'] ?? 'USD',
                'reference' => $input['reference'] ?? null,
                'notes' => $input['notes'] ?? null,
            ]);

            $allocated = 0;
            foreach ($input['allocations'] ?? [] as $alloc) {
                $invoice = ArInvoice::find($alloc['invoice_id']);
                if (! $invoice || $alloc['amount'] <= 0) {
                    continue;
                }
                ArInvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'amount' => $alloc['amount'],
                ]);
                $allocated += $alloc['amount'];
                $invoice->increment('amount_allocated', $alloc['amount']);
                $invoice->refresh();
                $invoice->update([
                    'status' => (float) $invoice->total <= (float) $invoice->amount_allocated ? 'paid' : 'partially_paid',
                ]);
            }

            return $payment;
        });
    }

    /**
     * Create credit note adjustment and optionally post reversal journal.
     */
    public function createCreditNote(ArInvoice $invoice, float $amount, string $reason = '', array $accountCodes = []): ArInvoiceAdjustment
    {
        $receivableCode = $accountCodes['receivable'] ?? '1100';
        $revenueCode = $accountCodes['revenue'] ?? '4100';

        return DB::transaction(function () use ($invoice, $amount, $reason, $receivableCode, $revenueCode) {
            $adjNumber = 'CN-' . $invoice->invoice_number . '-' . ($invoice->adjustments()->count() + 1);
            $adjustment = ArInvoiceAdjustment::create([
                'invoice_id' => $invoice->id,
                'type' => 'credit_note',
                'adjustment_number' => $adjNumber,
                'amount' => -abs($amount),
                'reason' => $reason,
                'adjustment_date' => now()->toDateString(),
            ]);

            $journal = $this->journalService->post(
                [
                    ['account_code' => $revenueCode, 'debit' => abs($amount), 'credit' => 0, 'client_id' => $invoice->client_id],
                    ['account_code' => $receivableCode, 'debit' => 0, 'credit' => abs($amount), 'client_id' => $invoice->client_id],
                ],
                [
                    'description' => 'Credit note ' . $adjNumber . ' - ' . $reason,
                    'journal_date' => $adjustment->adjustment_date->toDateString(),
                    'journal_number' => $adjNumber,
                ],
            );
            $adjustment->update(['journal_id' => $journal->id]);
            $this->recalculateInvoiceTotals($invoice->fresh());
            return $adjustment->fresh();
        });
    }

    protected function recalculateInvoiceTotals(ArInvoice $invoice): void
    {
        $invoice->load(['lines', 'adjustments']);
        $subtotal = $invoice->lines->sum('amount');
        $adjustmentsTotal = $invoice->adjustments->sum('amount');
        $invoice->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + $adjustmentsTotal,
            'tax_amount' => 0,
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'AR-' . now()->format('Ymd');
        $last = ArInvoice::where('invoice_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->invoice_number, -4) + 1 : 1;
        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
