<?php

namespace Database\Seeders;

use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillLine;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\ApPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\ApVoucher;
use App\Modules\AccountsPayable\Infrastructure\Models\ApCheck;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillAdjustment;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsPayableDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /** @var JournalService $journalService */
            $journalService = app(JournalService::class);

            $vendors = [
                Vendor::firstOrCreate(
                    ['code' => 'AP-TRANS'],
                    [
                        'name' => 'Demo Transporter',
                        'category' => 'transport',
                        'tax_id' => 'T-123456',
                        'currency' => 'USD',
                        'payment_terms_days' => 30,
                        'is_active' => true,
                        'preferred_payment_method' => 'check',
                    ],
                ),
                Vendor::firstOrCreate(
                    ['code' => 'AP-WH'],
                    [
                        'name' => 'Demo Warehouse Partner',
                        'category' => 'warehouse',
                        'tax_id' => 'W-987654',
                        'currency' => 'USD',
                        'payment_terms_days' => 45,
                        'is_active' => true,
                        'preferred_payment_method' => 'ach',
                    ],
                ),
                Vendor::firstOrCreate(
                    ['code' => 'AP-CUST'],
                    [
                        'name' => 'Demo Customs Broker',
                        'category' => 'customs',
                        'tax_id' => 'C-111222',
                        'currency' => 'EUR',
                        'payment_terms_days' => 30,
                        'is_active' => true,
                        'preferred_payment_method' => 'ach',
                    ],
                ),
            ];

            $bankAccount = BankAccount::first();

            $createBillScenario = function (
                Vendor $vendor,
                string $billNumber,
                float $amount,
                int $billDaysAgo,
                int $dueDaysOffset,
                string $pattern
            ) use ($journalService, $bankAccount): void {
                ApBill::where('bill_number', $billNumber)->delete();
                Journal::where('journal_number', 'AP-BILL-' . $billNumber)->delete();

                $bill = ApBill::create([
                    'vendor_id' => $vendor->id,
                    'bill_number' => $billNumber,
                    'bill_date' => now()->subDays($billDaysAgo)->toDateString(),
                    'due_date' => now()->addDays($dueDaysOffset)->toDateString(),
                    'status' => 'draft',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                    'amount_allocated' => 0,
                    'currency' => $vendor->currency,
                    'notes' => 'Demo AP bill ' . $billNumber,
                ]);

                ApBillLine::create([
                    'bill_id' => $bill->id,
                    'description' => 'Demo services ' . $billNumber,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'vendor_id' => $vendor->id,
                ]);

                $bill->update([
                    'subtotal' => $amount,
                    'total' => $amount,
                ]);

                $bill->update(['status' => 'pending_approval']);
                $bill->update(['status' => 'approved']);

                $journal = $journalService->post(
                    [
                        ['account_code' => '530000', 'debit' => $amount, 'credit' => 0, 'vendor_id' => $vendor->id],
                        ['account_code' => '211100', 'debit' => 0, 'credit' => $amount, 'vendor_id' => $vendor->id],
                    ],
                    [
                        'description' => 'AP Bill ' . $billNumber,
                        'journal_date' => $bill->bill_date,
                        'journal_number' => 'AP-BILL-' . $billNumber,
                        'event_type' => 'vendor-invoice-approved',
                    ],
                );
                $bill->update([
                    'journal_id' => $journal->id,
                    'status' => 'issued',
                ]);

                if ($pattern === 'unpaid') {
                    return;
                }

                if ($pattern === 'partial' || $pattern === 'full') {
                    $payAmount = $pattern === 'partial' ? round($amount * 0.4, 2) : $amount;
                    $payment = ApPayment::create([
                        'vendor_id' => $vendor->id,
                        'payment_date' => now()->subDays(2)->toDateString(),
                        'amount' => $payAmount,
                        'currency' => $vendor->currency,
                        'reference' => $billNumber . '-PMT',
                        'notes' => $pattern === 'partial' ? 'Partial payment' : 'Full payment',
                        'payment_method' => $vendor->preferred_payment_method ?? 'ach',
                        'bank_account_id' => $bankAccount?->id,
                    ]);

                    ApBillPayment::create([
                        'bill_id' => $bill->id,
                        'payment_id' => $payment->id,
                        'amount' => $payAmount,
                    ]);

                    $bill->update([
                        'amount_allocated' => $payAmount,
                        'status' => $pattern === 'partial' ? 'partially_paid' : 'paid',
                    ]);

                    ApVoucher::create([
                        'voucher_number' => 'AP-V-DEMO-' . $billNumber,
                        'payment_id' => $payment->id,
                        'voucher_date' => $payment->payment_date,
                    ]);

                    if ($payment->payment_method === 'check') {
                        ApCheck::create([
                            'check_number' => '9000' . $bill->id,
                            'payment_id' => $payment->id,
                            'bank_account_id' => $bankAccount?->id,
                            'check_date' => $payment->payment_date,
                            'amount' => $payment->amount,
                            'payee' => $vendor->name,
                            'status' => ApCheck::STATUS_PRINTED,
                        ]);
                    }
                }

                if ($pattern === 'credit-partial') {
                    $creditAmount = round($amount * 0.2, 2);
                    $adj = ApBillAdjustment::create([
                        'bill_id' => $bill->id,
                        'type' => 'credit_note',
                        'adjustment_number' => 'AP-CN-' . $billNumber,
                        'amount' => -$creditAmount,
                        'reason' => 'Demo vendor credit / dispute resolution',
                        'adjustment_date' => now()->subDays(1)->toDateString(),
                    ]);

                    $creditJournal = $journalService->post(
                        [
                            ['account_code' => '211100', 'debit' => $creditAmount, 'credit' => 0, 'vendor_id' => $vendor->id],
                            ['account_code' => '530000', 'debit' => 0, 'credit' => $creditAmount, 'vendor_id' => $vendor->id],
                        ],
                        [
                            'description' => 'Vendor credit note ' . $adj->adjustment_number,
                            'journal_date' => $adj->adjustment_date,
                            'journal_number' => $adj->adjustment_number,
                            'event_type' => 'vendor-credit-note',
                        ],
                    );
                    $adj->update(['journal_id' => $creditJournal->id]);

                    $bill->refresh();
                    $bill->update([
                        'total' => $bill->total - $creditAmount,
                    ]);
                }
            };

            $createBillScenario($vendors[0], 'AP-DEMO-0001', 800, 5, 25, 'partial');
            $createBillScenario($vendors[0], 'AP-DEMO-0002', 1200, 20, -3, 'unpaid');

            $createBillScenario($vendors[1], 'AP-WH-0001', 1500, 10, 20, 'full');
            $createBillScenario($vendors[1], 'AP-WH-0002', 600, 25, -10, 'credit-partial');

            $createBillScenario($vendors[2], 'AP-CUST-0001', 900, 15, 15, 'partial');
            $createBillScenario($vendors[2], 'AP-CUST-0002', 500, 40, -5, 'unpaid');
        });
    }
}

