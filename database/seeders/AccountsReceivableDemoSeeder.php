<?php

namespace Database\Seeders;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceLine;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArPayment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceAdjustment;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsReceivableDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /** @var JournalService $journalService */
            $journalService = app(JournalService::class);

            // 1) Create a few demo clients (different currencies and volumes)
            $clients = [
                BillingClient::firstOrCreate(
                    ['code' => 'AR-DEMO'],
                    ['name' => 'AR Demo Client', 'currency' => 'USD', 'is_active' => true],
                ),
                BillingClient::firstOrCreate(
                    ['code' => 'AR-WAREHOUSE'],
                    ['name' => 'Warehouse Client', 'currency' => 'USD', 'is_active' => true],
                ),
                BillingClient::firstOrCreate(
                    ['code' => 'AR-INTL'],
                    ['name' => 'International Client', 'currency' => 'EUR', 'is_active' => true],
                ),
            ];

            // Helper closure to create an issued invoice with optional partial/ full payment and credit note
            $createInvoice = function (
                BillingClient $client,
                string $number,
                float $amount,
                int $invoiceDaysAgo,
                int $dueDaysOffset,
                string $statusPattern
            ) use ($journalService): void {
                // Always reset any previous demo invoice and its journal number so the scenario is deterministic
                ArInvoice::where('invoice_number', $number)->delete();
                Journal::where('journal_number', 'AR-INV-' . $number)->delete();
                // Remove any previous payments tied to this demo invoice number
                ArPayment::whereIn('reference', [
                    $number . '-PMT-1',
                    $number . '-PMT-FULL',
                ])->delete();

                $invoice = ArInvoice::firstOrCreate(
                    ['invoice_number' => $number],
                    [
                        'client_id' => $client->id,
                        'invoice_date' => now()->subDays($invoiceDaysAgo)->toDateString(),
                        'due_date' => now()->addDays($dueDaysOffset)->toDateString(),
                        'status' => 'draft',
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'total' => 0,
                        'amount_allocated' => 0,
                        'currency' => $client->currency,
                    ],
                );

                if (! $invoice->lines()->exists()) {
                    ArInvoiceLine::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Logistics services - ' . $number,
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'amount' => $amount,
                        'client_id' => $client->id,
                    ]);

                    $invoice->update([
                        'subtotal' => $amount,
                        'total' => $amount,
                    ]);
                }

                if (! $invoice->journal_id) {
                    $journal = $journalService->post(
                        [
                            ['account_code' => '121100', 'debit' => $amount, 'credit' => 0, 'client_id' => $client->id],
                            ['account_code' => '423000', 'debit' => 0, 'credit' => $amount, 'client_id' => $client->id],
                        ],
                        [
                            'description' => 'AR Invoice ' . $number,
                            'journal_date' => $invoice->invoice_date,
                            'journal_number' => 'AR-INV-' . $number,
                            'event_type' => 'client-invoice-issued',
                        ],
                    );
                    $invoice->update([
                        'journal_id' => $journal->id,
                        'status' => 'issued',
                    ]);
                }

                // Apply pattern: none, partial payment, full payment, credit note
                if ($statusPattern === 'partial') {
                    if (! ArPayment::where('reference', $number . '-PMT-1')->exists()) {
                        $paymentAmount = round($amount * 0.4, 2);
                        $payment = ArPayment::create([
                            'client_id' => $client->id,
                            'payment_date' => now()->subDays(3)->toDateString(),
                            'amount' => $paymentAmount,
                            'currency' => $client->currency,
                            'reference' => $number . '-PMT-1',
                            'notes' => 'Partial payment',
                        ]);
                        $invoice->invoicePayments()->create([
                            'payment_id' => $payment->id,
                            'amount' => $paymentAmount,
                        ]);
                        $invoice->increment('amount_allocated', $paymentAmount);
                        $invoice->refresh();
                        $invoice->update([
                            'status' => (float) $invoice->total <= (float) $invoice->amount_allocated ? 'paid' : 'partially_paid',
                        ]);
                    }
                } elseif ($statusPattern === 'full') {
                    if (! ArPayment::where('reference', $number . '-PMT-FULL')->exists()) {
                        $payment = ArPayment::create([
                            'client_id' => $client->id,
                            'payment_date' => now()->subDays(1)->toDateString(),
                            'amount' => $amount,
                            'currency' => $client->currency,
                            'reference' => $number . '-PMT-FULL',
                            'notes' => 'Full payment',
                        ]);
                        $invoice->invoicePayments()->create([
                            'payment_id' => $payment->id,
                            'amount' => $amount,
                        ]);
                        $invoice->update([
                            'amount_allocated' => $amount,
                            'status' => 'paid',
                        ]);
                    }
                } elseif ($statusPattern === 'credit-partial') {
                    // Apply a partial credit note for this invoice
                    if (! ArInvoiceAdjustment::where('adjustment_number', $number . '-CN-1')->exists()) {
                        $creditAmount = round($amount * 0.2, 2);
                        // Ensure any prior credit note journal is removed
                        Journal::where('journal_number', $number . '-CN-1')->delete();

                        $adj = ArInvoiceAdjustment::create([
                            'invoice_id' => $invoice->id,
                            'type' => 'credit_note',
                            'adjustment_number' => $number . '-CN-1',
                            'amount' => -$creditAmount,
                            'reason' => 'Demo discount / dispute resolution',
                            'adjustment_date' => now()->subDays(2)->toDateString(),
                        ]);
                        $journal = $journalService->post(
                            [
                                ['account_code' => '423000', 'debit' => $creditAmount, 'credit' => 0, 'client_id' => $client->id],
                                ['account_code' => '121100', 'debit' => 0, 'credit' => $creditAmount, 'client_id' => $client->id],
                            ],
                            [
                                'description' => 'Credit note ' . $adj->adjustment_number,
                                'journal_date' => $adj->adjustment_date,
                                'journal_number' => $adj->adjustment_number,
                                'event_type' => 'client-credit-note',
                            ],
                        );
                        $adj->update(['journal_id' => $journal->id]);

                        $invoice->refresh();
                        $invoice->update([
                            'total' => $invoice->total - $creditAmount,
                        ]);
                    }
                }
            };

            // Seed scenarios per client
            // Client 1 (AR-DEMO, USD): one partial, one full, one overdue unpaid
            $createInvoice($clients[0], 'AR-DEMO-0001', 500, 10, 20, 'partial');
            $createInvoice($clients[0], 'AR-DEMO-0002', 750, 20, 10, 'full');
            $createInvoice($clients[0], 'AR-DEMO-0003', 900, 40, -5, 'none'); // overdue, no payment

            // Client 2 (warehouse, USD): mix including credit note
            $createInvoice($clients[1], 'AR-WH-0001', 1200, 5, 25, 'partial');
            $createInvoice($clients[1], 'AR-WH-0002', 300, 15, -2, 'credit-partial'); // overdue with credit note

            // Client 3 (international, EUR): multi-currency case
            $createInvoice($clients[2], 'AR-EU-0001', 1000, 8, 22, 'partial');
            $createInvoice($clients[2], 'AR-EU-0002', 650, 35, -3, 'none'); // aged overdue EUR
        });
    }
}

