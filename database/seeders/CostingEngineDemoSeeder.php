<?php

namespace Database\Seeders;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Database\Seeder;

class CostingEngineDemoSeeder extends Seeder
{
    /**
     * Seed minimal data for Costing & Profitability: clients, AR invoices (revenue), and posted journal lines (cost by client/warehouse).
     */
    public function run(): void
    {
        $clients = $this->ensureClients();
        $this->ensureArInvoicesForClients($clients);
        $this->ensureCostJournalLines($clients);
    }

    /** @return \Illuminate\Support\Collection<int, BillingClient> */
    private function ensureClients(): \Illuminate\Support\Collection
    {
        $codes = ['DEMO-A', 'DEMO-B'];
        $clients = collect();
        foreach ($codes as $code) {
            $client = BillingClient::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'Demo Client ' . substr($code, -1),
                    'currency' => 'USD',
                    'is_active' => true,
                ],
            );
            $clients->push($client);
        }

        return $clients;
    }

    /** @param \Illuminate\Support\Collection<int, BillingClient> $clients */
    private function ensureArInvoicesForClients($clients): void
    {
        foreach ($clients as $client) {
            $exists = ArInvoice::where('client_id', $client->id)
                ->whereIn('status', ['issued', 'partially_paid', 'paid'])
                ->exists();
            if ($exists) {
                continue;
            }
            ArInvoice::create([
                'client_id' => $client->id,
                'invoice_number' => 'INV-DEMO-' . $client->code . '-' . now()->format('Ym'),
                'invoice_date' => now()->subDays(15)->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => 'issued',
                'subtotal' => 5000.00,
                'tax_amount' => 0,
                'total' => 5000.00,
                'amount_allocated' => 0,
                'currency' => 'USD',
                'notes' => 'Demo invoice for profitability',
            ]);
        }
    }

    /** @param \Illuminate\Support\Collection<int, BillingClient> $clients */
    private function ensureCostJournalLines($clients): void
    {
        $expenseAccount = Account::where('code', '5200')->first(); // Transport Expense
        $cashAccount = Account::where('code', '1400')->first();
        if (! $expenseAccount || ! $cashAccount) {
            return;
        }

        $journal = Journal::firstOrCreate(
            ['journal_number' => 'J-COST-DEMO'],
            [
                'journal_date' => now()->subDays(10),
                'period' => now()->format('Y-m'),
                'description' => 'Demo cost allocation for profitability',
                'status' => 'posted',
                'posted_at' => now(),
            ],
        );

        if ($journal->lines()->whereNotNull('client_id')->exists()) {
            return;
        }

        $totalDebit = 0;
        foreach ($clients as $client) {
            $amount = 1200.00;
            $totalDebit += $amount;
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'description' => 'Demo transport cost ' . $client->code,
                'debit' => $amount,
                'credit' => 0,
                'client_id' => $client->id,
            ]);
        }

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'description' => 'Demo cost allocation clearing',
            'debit' => 0,
            'credit' => $totalDebit,
        ]);

        $warehouse = Warehouse::where('is_active', true)->first();
        if ($warehouse) {
            $revAccount = Account::where('code', '4200')->first(); // Storage Revenue
            if ($revAccount) {
                $revAmount = 800.00;
                $expAmount = 300.00;
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $revAccount->id,
                    'description' => 'Demo warehouse revenue',
                    'debit' => 0,
                    'credit' => $revAmount,
                    'warehouse_id' => $warehouse->id,
                ]);
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $expenseAccount->id,
                    'description' => 'Demo warehouse cost',
                    'debit' => $expAmount,
                    'credit' => 0,
                    'warehouse_id' => $warehouse->id,
                ]);
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAccount->id,
                    'description' => 'Warehouse demo clearing',
                    'debit' => $revAmount - $expAmount,
                    'credit' => 0,
                ]);
            }
        }
    }
}
