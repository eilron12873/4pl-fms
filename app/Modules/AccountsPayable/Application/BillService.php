<?php

namespace App\Modules\AccountsPayable\Application;

use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillAdjustment;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillLine;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\ApCheck;
use App\Modules\AccountsPayable\Infrastructure\Models\ApPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\ApVoucher;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\CoreAccounting\Application\JournalService;
use Illuminate\Support\Facades\DB;

class BillService
{
    public function __construct(
        protected JournalService $journalService,
    ) {
    }

    /**
     * After a journal is posted for vendor-invoice-approved, record a bill line.
     *
     * @param  array{vendor_id: int, journal_id: int, source_reference: string, source_type: string, amount: float, description: string, bill_date: string}  $context
     */
    public function createBillLineFromJournal(array $context): ApBillLine
    {
        return DB::transaction(function () use ($context) {
            $vendorId = (int) $context['vendor_id'];
            $journalId = (int) $context['journal_id'];
            $amount = (float) $context['amount'];
            $billDate = $context['bill_date'] ?? now()->toDateString();
            $description = $context['description'] ?? 'Bill line';

            $bill = ApBill::where('vendor_id', $vendorId)
                ->where('status', 'draft')
                ->whereDate('bill_date', '>=', now()->startOfMonth()->toDateString())
                ->whereDate('bill_date', '<=', now()->endOfMonth()->toDateString())
                ->first();

            if (! $bill) {
                $vendor = Vendor::findOrFail($vendorId);
                $bill = ApBill::create([
                    'vendor_id' => $vendorId,
                    'bill_number' => $this->generateBillNumber(),
                    'bill_date' => $billDate,
                    'due_date' => now()->addDays($vendor->payment_terms_days)->toDateString(),
                    'status' => 'draft',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                    'currency' => $vendor->currency,
                ]);
            }

            $line = ApBillLine::create([
                'bill_id' => $bill->id,
                'journal_id' => $journalId,
                'source_type' => $context['source_type'] ?? null,
                'source_reference' => $context['source_reference'] ?? null,
                'description' => $description,
                'quantity' => 1,
                'unit_price' => $amount,
                'amount' => $amount,
                'vendor_id' => $vendorId,
            ]);

            $this->recalculateBillTotals($bill);
            return $line;
        });
    }

    /**
     * Create a draft bill with manual line items (no journal). Use for AP Entry.
     *
     * @param  array{vendor_id: int, bill_date: string, due_date?: string, currency?: string, notes?: string, lines: array<array{description: string, amount: float}>}  $input
     */
    public function createManualBill(array $input): ApBill
    {
        return DB::transaction(function () use ($input) {
            $vendorId = (int) $input['vendor_id'];
            $vendor = Vendor::findOrFail($vendorId);
            $billDate = $input['bill_date'] ?? now()->toDateString();
            $dueDate = $input['due_date'] ?? now()->addDays($vendor->payment_terms_days)->toDateString();
            $currency = $input['currency'] ?? $vendor->currency;
            $lines = $input['lines'] ?? [];
            if (empty($lines)) {
                throw new \InvalidArgumentException('At least one line is required.');
            }

            $bill = ApBill::create([
                'vendor_id' => $vendorId,
                'purchase_order_id' => isset($input['purchase_order_id']) ? (int) $input['purchase_order_id'] : null,
                'bill_number' => $this->generateBillNumber(),
                'bill_date' => $billDate,
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
                ApBillLine::create([
                    'bill_id' => $bill->id,
                    'description' => $description ?: 'Line',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'vendor_id' => $vendorId,
                ]);
            }

            $this->recalculateBillTotals($bill);
            return $bill->fresh();
        });
    }

    /**
     * Update a draft bill (header and lines). Only allowed when status is draft.
     */
    public function updateDraftBill(ApBill $bill, array $input): ApBill
    {
        if ($bill->isIssued()) {
            throw new \InvalidArgumentException('Cannot edit an issued bill.');
        }

        return DB::transaction(function () use ($bill, $input) {
            $bill->update([
                'bill_date' => $input['bill_date'] ?? $bill->bill_date->toDateString(),
                'due_date' => $input['due_date'] ?? $bill->due_date->toDateString(),
                'currency' => $input['currency'] ?? $bill->currency,
                'notes' => $input['notes'] ?? $bill->notes,
            ]);
            if (isset($input['vendor_id'])) {
                $bill->update(['vendor_id' => (int) $input['vendor_id']]);
            }

            $bill->lines()->delete();
            $lines = $input['lines'] ?? [];
            $vendorId = (int) $bill->vendor_id;
            foreach ($lines as $line) {
                $amount = (float) ($line['amount'] ?? 0);
                $description = $line['description'] ?? '';
                if ($amount <= 0 && $description === '') {
                    continue;
                }
                ApBillLine::create([
                    'bill_id' => $bill->id,
                    'description' => $description ?: 'Line',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'vendor_id' => $vendorId,
                ]);
            }

            $this->recalculateBillTotals($bill->fresh());
            return $bill->fresh();
        });
    }

    public function issueBill(ApBill $bill, array $accountCodes = []): void
    {
        if ($bill->isIssued()) {
            return;
        }
        $payableCode = $accountCodes['payable'] ?? '2100';
        $expenseCode = $accountCodes['expense'] ?? '5200';
        $total = (float) $bill->total;

        DB::transaction(function () use ($bill, $payableCode, $expenseCode, $total) {
            if (! $bill->journal_id) {
                $journal = $this->journalService->post(
                    [
                        ['account_code' => $expenseCode, 'debit' => $total, 'credit' => 0],
                        ['account_code' => $payableCode, 'debit' => 0, 'credit' => $total],
                    ],
                    [
                        'description' => 'AP Bill ' . $bill->bill_number,
                        'journal_date' => $bill->bill_date->toDateString(),
                        'journal_number' => 'AP-BILL-' . $bill->bill_number,
                    ],
                );
                $bill->update(['journal_id' => $journal->id]);
            }
            $bill->update(['status' => 'issued']);
        });
    }

    /**
     * @param  array{vendor_id: int, payment_date: string, amount: float, currency: string, reference?: string, allocations: array<array{bill_id: int, amount: float}>}  $input
     */
    public function recordPayment(array $input): ApPayment
    {
        return DB::transaction(function () use ($input) {
            $paymentMethod = $input['payment_method'] ?? 'ach';
            $bankAccountId = isset($input['bank_account_id']) ? (int) $input['bank_account_id'] : null;

            $payment = ApPayment::create([
                'vendor_id' => $input['vendor_id'],
                'payment_date' => $input['payment_date'],
                'amount' => $input['amount'],
                'currency' => $input['currency'] ?? 'USD',
                'reference' => $input['reference'] ?? null,
                'notes' => $input['notes'] ?? null,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
            ]);

            foreach ($input['allocations'] ?? [] as $alloc) {
                $bill = ApBill::find($alloc['bill_id'] ?? null);
                if (! $bill || ($alloc['amount'] ?? 0) <= 0) {
                    continue;
                }
                ApBillPayment::create([
                    'bill_id' => $bill->id,
                    'payment_id' => $payment->id,
                    'amount' => $alloc['amount'],
                ]);
                $bill->increment('amount_allocated', $alloc['amount']);
                $bill->refresh();
                $bill->update([
                    'status' => (float) $bill->total <= (float) $bill->amount_allocated ? 'paid' : 'partially_paid',
                ]);
            }

            ApVoucher::create([
                'voucher_number' => $this->generateVoucherNumber(),
                'payment_id' => $payment->id,
                'voucher_date' => $payment->payment_date->toDateString(),
            ]);

            if ($paymentMethod === 'check') {
                $vendor = Vendor::find($payment->vendor_id);
                $payee = $vendor ? ($vendor->name) : 'Payee';
                $checkNumber = $this->generateCheckNumber($bankAccountId);
                ApCheck::create([
                    'check_number' => $checkNumber,
                    'payment_id' => $payment->id,
                    'bank_account_id' => $bankAccountId,
                    'check_date' => $payment->payment_date->toDateString(),
                    'amount' => $payment->amount,
                    'payee' => $payee,
                    'status' => ApCheck::STATUS_PRINTED,
                ]);
            }

            return $payment;
        });
    }

    protected function generateVoucherNumber(): string
    {
        $prefix = 'AP-V-' . date('Y') . '-';
        $last = ApVoucher::where('voucher_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->voucher_number, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    protected function generateCheckNumber(?int $bankAccountId): string
    {
        $query = ApCheck::query();
        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }
        $last = $query->orderByDesc('id')->first();
        $next = $last ? (int) $last->check_number + 1 : 1;
        return (string) $next;
    }

    /**
     * Vendor credit note: reduces our liability. Adjustment negative, post DR AP CR Expense.
     */
    public function createCreditNote(ApBill $bill, float $amount, string $reason = '', array $accountCodes = []): ApBillAdjustment
    {
        $payableCode = $accountCodes['payable'] ?? '2100';
        $expenseCode = $accountCodes['expense'] ?? '5200';

        return DB::transaction(function () use ($bill, $amount, $reason, $payableCode, $expenseCode) {
            $adjNumber = 'CN-' . $bill->bill_number . '-' . ($bill->adjustments()->count() + 1);
            $adjustment = ApBillAdjustment::create([
                'bill_id' => $bill->id,
                'type' => 'credit_note',
                'adjustment_number' => $adjNumber,
                'amount' => -abs($amount),
                'reason' => $reason,
                'adjustment_date' => now()->toDateString(),
            ]);

            $journal = $this->journalService->post(
                [
                    ['account_code' => $payableCode, 'debit' => abs($amount), 'credit' => 0],
                    ['account_code' => $expenseCode, 'debit' => 0, 'credit' => abs($amount)],
                ],
                [
                    'description' => 'Vendor credit note ' . $adjNumber . ' - ' . $reason,
                    'journal_date' => $adjustment->adjustment_date->toDateString(),
                    'journal_number' => $adjNumber,
                ],
            );
            $adjustment->update(['journal_id' => $journal->id]);
            $this->recalculateBillTotals($bill->fresh());
            return $adjustment->fresh();
        });
    }

    protected function recalculateBillTotals(ApBill $bill): void
    {
        $bill->load(['lines', 'adjustments']);
        $subtotal = $bill->lines->sum('amount');
        $adjustmentsTotal = $bill->adjustments->sum('amount');
        $bill->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + $adjustmentsTotal,
            'tax_amount' => 0,
        ]);
    }

    protected function generateBillNumber(): string
    {
        $prefix = 'AP-' . now()->format('Ymd');
        $last = ApBill::where('bill_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->bill_number, -4) + 1 : 1;
        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
