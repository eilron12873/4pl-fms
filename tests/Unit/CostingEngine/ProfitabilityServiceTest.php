<?php

namespace Tests\Unit\CostingEngine;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\CostingEngine\Application\ProfitabilityService;
use App\Modules\CostingEngine\Infrastructure\Models\CostingEngineSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_profitability_includes_margin_calculation(): void
    {
        CostingEngineSetting::updateOrCreate(['setting_key' => 'functional_currency'], ['setting_value' => ['USD']]);
        CostingEngineSetting::updateOrCreate(['setting_key' => 'fx_rates'], ['setting_value' => ['USD' => 1.0]]);

        $client = BillingClient::create([
            'code' => 'C-COST-1',
            'name' => 'Client Costing',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        ArInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-COST-1',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'issued',
            'subtotal' => 1000,
            'tax_amount' => 0,
            'total' => 1000,
            'amount_allocated' => 0,
            'currency' => 'USD',
        ]);

        $expenseAccount = Account::create([
            'code' => '530001',
            'name' => 'Test Expense',
            'type' => 'expense',
            'category' => 'expense',
            'is_active' => true,
        ]);
        $clearing = Account::create([
            'code' => '112199',
            'name' => 'Clearing',
            'type' => 'asset',
            'category' => 'asset',
            'is_active' => true,
        ]);

        $journal = Journal::create([
            'journal_number' => 'J-COST-TST',
            'journal_date' => now()->toDateString(),
            'period' => now()->format('Y-m'),
            'description' => 'Costing test',
            'status' => 'posted',
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $expenseAccount->id,
            'description' => 'Cost',
            'debit' => 400,
            'credit' => 0,
            'client_id' => $client->id,
        ]);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $clearing->id,
            'description' => 'Offset',
            'debit' => 0,
            'credit' => 400,
        ]);

        $service = app(ProfitabilityService::class);
        $rows = $service->clientProfitability();

        $this->assertCount(1, $rows);
        $this->assertEquals(1000.0, $rows[0]['revenue']);
        $this->assertEquals(400.0, $rows[0]['cost']);
        $this->assertEquals(600.0, $rows[0]['margin']);
    }
}

