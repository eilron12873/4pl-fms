<?php

namespace Database\Seeders;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\CostingEngine\Application\AllocationService;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationResult;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRule;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\CostingEngine\Infrastructure\Models\CostingEngineSetting;
use App\Modules\CostingEngine\Infrastructure\Models\CostingSavedFilter;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Database\Seeder;

class CostingEngineDemoSeeder extends Seeder
{
    /**
     * Seed minimal data for Costing & Profitability: clients, AR invoices (revenue), and posted journal lines (cost by client/warehouse).
     */
    public function run(): void
    {
        $this->resetDemoData();

        $clients = $this->ensureClients();
        $this->ensureArInvoicesForClients($clients);
        $this->ensureCostJournalLines($clients);
        $this->ensureCostingSettings();
        $this->ensureAllocationRulesAndRun();
        $this->ensureSavedFilters();
    }

    private function resetDemoData(): void
    {
        // Deterministic demo reset for repeatable scenario testing.
        ArInvoice::where('invoice_number', 'like', 'INV-DEMO-%')->delete();

        $journal = Journal::where('journal_number', 'J-COST-DEMO')->first();
        if ($journal) {
            JournalLine::where('journal_id', $journal->id)->delete();
        }

        CostingAllocationResult::query()
            ->whereHas('rule', fn ($q) => $q->where('name', 'like', 'DEMO-%'))
            ->delete();

        CostingAllocationRule::query()
            ->where('name', 'like', 'DEMO-%')
            ->delete();
    }

    /** @return \Illuminate\Support\Collection<int, BillingClient> */
    private function ensureClients(): \Illuminate\Support\Collection
    {
        $codes = ['DEMO-A', 'DEMO-B', 'DEMO-EU'];
        $clients = collect();
        foreach ($codes as $code) {
            $client = BillingClient::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'Demo Client ' . substr($code, -1),
                    'currency' => $code === 'DEMO-EU' ? 'EUR' : 'USD',
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
        $seedBase = now()->subDays(20);

        foreach ($clients as $client) {
            $amountIssued = $client->currency === 'EUR' ? 2800.00 : 4200.00;
            $amountPartial = $client->currency === 'EUR' ? 1900.00 : 3000.00;
            $amountPaid = $client->currency === 'EUR' ? 1200.00 : 2000.00;

            ArInvoice::updateOrCreate(
                ['invoice_number' => 'INV-DEMO-' . $client->code . '-ISSUED'],
                [
                    'client_id' => $client->id,
                    'invoice_date' => $seedBase->copy()->subDays(20)->toDateString(),
                    'due_date' => $seedBase->copy()->addDays(30)->toDateString(),
                    'status' => 'issued',
                    'subtotal' => $amountIssued,
                    'tax_amount' => 0,
                    'total' => $amountIssued,
                    'amount_allocated' => 0,
                    'currency' => $client->currency,
                    'notes' => 'Demo issued invoice for profitability',
                ],
            );

            ArInvoice::updateOrCreate(
                ['invoice_number' => 'INV-DEMO-' . $client->code . '-PARTIAL'],
                [
                    'client_id' => $client->id,
                    'invoice_date' => $seedBase->copy()->subDays(14)->toDateString(),
                    'due_date' => $seedBase->copy()->addDays(30)->toDateString(),
                    'status' => 'partially_paid',
                    'subtotal' => $amountPartial,
                    'tax_amount' => 0,
                    'total' => $amountPartial,
                    'amount_allocated' => round($amountPartial * 0.4, 2),
                    'currency' => $client->currency,
                    'notes' => 'Demo partially paid invoice for profitability',
                ],
            );

            ArInvoice::updateOrCreate(
                ['invoice_number' => 'INV-DEMO-' . $client->code . '-PAID'],
                [
                    'client_id' => $client->id,
                    'invoice_date' => $seedBase->copy()->subDays(8)->toDateString(),
                    'due_date' => $seedBase->copy()->addDays(30)->toDateString(),
                    'status' => 'paid',
                    'subtotal' => $amountPaid,
                    'tax_amount' => 0,
                    'total' => $amountPaid,
                    'amount_allocated' => $amountPaid,
                    'currency' => $client->currency,
                    'notes' => 'Demo paid invoice for profitability',
                ],
            );

            ArInvoice::updateOrCreate(
                ['invoice_number' => 'INV-DEMO-' . $client->code . '-DRAFT'],
                [
                    'client_id' => $client->id,
                    'invoice_date' => $seedBase->copy()->subDays(5)->toDateString(),
                    'due_date' => $seedBase->copy()->addDays(30)->toDateString(),
                    'status' => 'draft',
                    'subtotal' => 999.00,
                    'tax_amount' => 0,
                    'total' => 999.00,
                    'amount_allocated' => 0,
                    'currency' => $client->currency,
                    'notes' => 'Demo draft invoice (should not be counted by profitability)',
                ],
            );
        }
    }

    /** @param \Illuminate\Support\Collection<int, BillingClient> $clients */
    private function ensureCostJournalLines($clients): void
    {
        $expenseAccount = Account::where('code', '530000')->first(); // Transport Cost
        $cashAccount = Account::where('code', '112100')->first(); // Cash in Bank - BDO
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
        $warehouse = Warehouse::where('is_active', true)->first();
        $shipmentRevenueAccount = Account::where('code', '421000')->first(); // Freight income (revenue prefix 41)
        $warehouseRevenueAccount = Account::where('code', '412000')->first(); // Storage revenue

        // 1) Client-level cost (expense debits where client_id set) + offset credits.
        $totalCreditOffset = 0.0;
        foreach ($clients as $client) {
            // Tune costs to create both positive and negative client margins.
            $cost = match ($client->code) {
                'DEMO-A' => 7500.00, // positive
                'DEMO-B' => 12000.00, // negative
                'DEMO-EU' => 5200.00, // EUR client, adjusted for fx by normalization layer
                default => 4000.00,
            };

            $totalCreditOffset += $cost;

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'description' => 'Demo client cost ' . $client->code,
                'debit' => $cost,
                'credit' => 0,
                'client_id' => $client->id,
            ]);
        }

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'description' => 'Demo client cost clearing',
            'debit' => 0,
            'credit' => $totalCreditOffset,
        ]);

        // 2) Shipment/Route/Project profitability (positive, negative, zero margin cases).
        $shipmentScenarios = [
            8000 => ['revenue' => 5000.00, 'cost' => 3000.00],
            8001 => ['revenue' => 2500.00, 'cost' => 4200.00],
            8002 => ['revenue' => 3200.00, 'cost' => 3200.00],
        ];

        foreach ($clients as $idx => $client) {
            $shipment = 8000 + $idx; // 8000..8002
            if (! isset($shipmentScenarios[$shipment])) {
                continue;
            }

            $route = 200 + $idx;
            $project = 3000 + $idx;
            $scenario = $shipmentScenarios[$shipment];

            if ($shipmentRevenueAccount) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $shipmentRevenueAccount->id,
                    'description' => 'Demo shipment revenue ' . $shipment,
                    'debit' => 0,
                    'credit' => (float) $scenario['revenue'],
                    'client_id' => $client->id,
                    'shipment_id' => $shipment,
                    'route_id' => $route,
                    'project_id' => $project,
                ]);
            }

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'description' => 'Demo shipment cost ' . $shipment,
                'debit' => (float) $scenario['cost'],
                'credit' => 0,
                'client_id' => $client->id,
                'shipment_id' => $shipment,
                'route_id' => $route,
                'project_id' => $project,
            ]);
        }

        // 3) Warehouse profitability (single active warehouse; create negative margin example).
        if ($warehouse && $warehouseRevenueAccount) {
            $warehouseRevenue = 2200.00;
            $warehouseCost = 2600.00; // negative warehouse margin

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $warehouseRevenueAccount->id,
                'description' => 'Demo warehouse revenue',
                'debit' => 0,
                'credit' => $warehouseRevenue,
                'warehouse_id' => $warehouse->id,
                'route_id' => 101,
                'project_id' => 1001,
                'shipment_id' => 5001,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'description' => 'Demo warehouse cost',
                'debit' => $warehouseCost,
                'credit' => 0,
                'warehouse_id' => $warehouse->id,
                'route_id' => 101,
                'project_id' => 1001,
                'shipment_id' => 5001,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $cashAccount->id,
                'description' => 'Warehouse demo clearing',
                'debit' => max(0, $warehouseCost - $warehouseRevenue),
                'credit' => 0,
            ]);
        }

        // 4) Missing-dimension rows: revenue/cost for route+project without shipment_id.
        if ($shipmentRevenueAccount) {
            $client = $clients->first();

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $shipmentRevenueAccount->id,
                'description' => 'Demo revenue missing shipment_id',
                'debit' => 0,
                'credit' => 400.00,
                'client_id' => $client?->id,
                'route_id' => 999,
                'project_id' => 9999,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'description' => 'Demo cost missing shipment_id',
                'debit' => 400.00,
                'credit' => 0,
                'client_id' => $client?->id,
                'route_id' => 999,
                'project_id' => 9999,
            ]);
        }
    }

    private function ensureCostingSettings(): void
    {
        CostingEngineSetting::updateOrCreate(
            ['setting_key' => 'revenue_prefixes'],
            ['setting_value' => ['41', '42', '43', '44', '45', '46']]
        );
        CostingEngineSetting::updateOrCreate(
            ['setting_key' => 'expense_prefixes'],
            ['setting_value' => ['51', '52', '53', '54', '55', '56', '57']]
        );
        CostingEngineSetting::updateOrCreate(
            ['setting_key' => 'enabled_dimensions'],
            ['setting_value' => ['client_id', 'shipment_id', 'route_id', 'warehouse_id', 'project_id']]
        );
        CostingEngineSetting::updateOrCreate(
            ['setting_key' => 'functional_currency'],
            ['setting_value' => ['USD']]
        );
        CostingEngineSetting::updateOrCreate(
            ['setting_key' => 'fx_rates'],
            ['setting_value' => ['USD' => 1.0, 'EUR' => 1.08]]
        );
    }

    private function ensureAllocationRulesAndRun(): void
    {
        CostingAllocationRule::updateOrCreate(
            ['name' => 'DEMO-FIXED-CLIENTS'],
            [
                'rule_type' => 'fixed',
                'target_dimension' => 'client_id',
                'source_dimension' => null,
                'fixed_amount' => 5000.00,
                'percentage' => null,
                'meta' => null,
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        // AllocationService uses meta.pool_amount as the pool amount for these rule types.
        CostingAllocationRule::updateOrCreate(
            ['name' => 'DEMO-REVENUE-PROJ'],
            [
                'rule_type' => 'revenue_proportion',
                'target_dimension' => 'project_id',
                'source_dimension' => null,
                'fixed_amount' => null,
                'percentage' => null,
                'meta' => ['pool_amount' => 4000.00],
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        CostingAllocationRule::updateOrCreate(
            ['name' => 'DEMO-VOLUME-SHIP'],
            [
                'rule_type' => 'volume',
                'target_dimension' => 'shipment_id',
                'source_dimension' => null,
                'fixed_amount' => null,
                'percentage' => null,
                'meta' => ['pool_amount' => 3000.00],
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        $allocationDate = now()->toDateString();

        CostingAllocationResult::query()
            ->where('allocation_date', $allocationDate)
            ->whereHas('rule', fn ($q) => $q->where('name', 'like', 'DEMO-%'))
            ->delete();

        /** @var AllocationService $allocationService */
        $allocationService = app(AllocationService::class);
        $allocationService->applyRulesForDate($allocationDate);
    }

    private function ensureSavedFilters(): void
    {
        $user = \App\Models\User::first();
        if (! $user) {
            return;
        }
        CostingSavedFilter::firstOrCreate(
            ['user_id' => $user->id, 'report_key' => 'client', 'name' => 'YTD'],
            ['filters' => ['from_date' => now()->startOfYear()->toDateString(), 'to_date' => now()->toDateString()]]
        );
    }
}
