<?php

namespace Tests\Unit\FinancialReporting;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\FinancialReporting\Application\AdvancedReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparative_income_statement_variance_pct_is_null_when_prior_is_zero(): void
    {
        config([
            'gl_statements.income_statement' => [
                [
                    'key' => 'revenue',
                    'label' => 'Revenue',
                    'account_prefixes' => ['4000'],
                ],
                [
                    'key' => 'cost_of_revenue',
                    'label' => 'COGS',
                    'account_prefixes' => ['5000'],
                ],
            ],
        ]);

        $revenueAccount = Account::create([
            'code' => '4000REV',
            'name' => 'Revenue Test',
            'type' => 'revenue',
            'parent_id' => null,
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);

        $cogsAccount = Account::create([
            'code' => '5000COGS',
            'name' => 'COGS Test',
            'type' => 'expense',
            'parent_id' => null,
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);

        $fromDate = '2026-03-01';
        $toDate = '2026-03-31';

        $journal = Journal::create([
            'journal_number' => 'J-FR-REV',
            'journal_date' => '2026-03-15',
            'period' => '2026-03',
            'description' => 'Test revenue posting',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        // Revenue amount should be positive (debit - credit).
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'description' => 'Revenue line',
            'debit' => 100,
            'credit' => 0,
        ]);

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $cogsAccount->id,
            'description' => 'COGS line',
            'debit' => 40,
            'credit' => 0,
        ]);

        /** @var AdvancedReportingService $service */
        $service = app(AdvancedReportingService::class);
        $result = $service->comparativeIncomeStatement($fromDate, $toDate);

        $revenueRow = collect($result['rows'] ?? [])->firstWhere('key', 'revenue');
        $this->assertNotNull($revenueRow);
        $this->assertSame(null, $revenueRow['variance_pct']);
        $this->assertEquals(100.0, $revenueRow['current']);
        $this->assertEquals(100.0, $revenueRow['variance']);
    }

    public function test_management_summary_computes_gross_margin_pct_with_rounded_amounts(): void
    {
        config([
            'gl_statements.income_statement' => [
                [
                    'key' => 'revenue',
                    'label' => 'Revenue',
                    'account_prefixes' => ['4000'],
                ],
                [
                    'key' => 'cost_of_revenue',
                    'label' => 'COGS',
                    'account_prefixes' => ['5000'],
                ],
            ],
        ]);

        $revenueAccount = Account::create([
            'code' => '4000REV2',
            'name' => 'Revenue Test 2',
            'type' => 'revenue',
            'parent_id' => null,
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);

        $cogsAccount = Account::create([
            'code' => '5000COGS2',
            'name' => 'COGS Test 2',
            'type' => 'expense',
            'parent_id' => null,
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);

        $fromDate = '2026-03-01';
        $toDate = '2026-03-31';

        $journal = Journal::create([
            'journal_number' => 'J-FR-GM',
            'journal_date' => '2026-03-20',
            'period' => '2026-03',
            'description' => 'Test gross margin posting',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'description' => 'Revenue line',
            'debit' => 100,
            'credit' => 0,
        ]);

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $cogsAccount->id,
            'description' => 'COGS line',
            'debit' => 40,
            'credit' => 0,
        ]);

        /** @var AdvancedReportingService $service */
        $service = app(AdvancedReportingService::class);
        $data = $service->managementSummary($fromDate, $toDate);

        $this->assertEquals(100.0, $data['total_revenue']);
        $this->assertEquals(40.0, $data['total_expense']);
        $this->assertEquals(60.0, $data['net_income']);
        $this->assertEquals(60.0, $data['gross_margin_pct']);
    }

    public function test_tax_summary_marks_revenue_sections_correctly(): void
    {
        config([
            'gl_statements.income_statement' => [
                [
                    'key' => 'revenue',
                    'label' => 'Revenue',
                    'account_prefixes' => ['4000'],
                ],
                [
                    'key' => 'other_income',
                    'label' => 'Other Income',
                    'account_prefixes' => ['4100'],
                ],
                [
                    'key' => 'cost_of_revenue',
                    'label' => 'COGS',
                    'account_prefixes' => ['5000'],
                ],
                [
                    'key' => 'other_expense',
                    'label' => 'Other Expense',
                    'account_prefixes' => ['5100'],
                ],
            ],
        ]);

        /** @var AdvancedReportingService $service */
        $service = app(AdvancedReportingService::class);
        $data = $service->taxSummary('2026-03-01', '2026-03-31');

        $sections = collect($data['sections'] ?? []);

        $revenue = $sections->firstWhere('key', 'revenue');
        $otherIncome = $sections->firstWhere('key', 'other_income');
        $cogs = $sections->firstWhere('key', 'cost_of_revenue');
        $otherExpense = $sections->firstWhere('key', 'other_expense');

        $this->assertTrue((bool) ($revenue['is_revenue'] ?? false));
        $this->assertTrue((bool) ($otherIncome['is_revenue'] ?? false));
        $this->assertFalse((bool) ($cogs['is_revenue'] ?? true));
        $this->assertFalse((bool) ($otherExpense['is_revenue'] ?? true));
    }
}

