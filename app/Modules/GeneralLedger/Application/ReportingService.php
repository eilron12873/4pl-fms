<?php

namespace App\Modules\GeneralLedger\Application;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportingService
{
    /**
     * Trial balance with optional period (date range).
     * When from_date/to_date are set: returns opening balance, period movement, and closing balance per account.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function trialBalance(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $baseQuery = JournalLine::query()
            ->select('journal_lines.account_id')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted');

        $totalsQuery = (clone $baseQuery);
        if ($fromDate && $toDate) {
            $totalsQuery->whereDate('journals.journal_date', '>=', $fromDate)
                ->whereDate('journals.journal_date', '<=', $toDate);
        }

        $totals = $totalsQuery
            ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->groupBy('journal_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $opening = collect();
        if ($fromDate) {
            $opening = (clone $baseQuery)
                ->whereDate('journals.journal_date', '<', $fromDate)
                ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit) as opening_debit, SUM(journal_lines.credit) as opening_credit')
                ->groupBy('journal_lines.account_id')
                ->get()
                ->keyBy('account_id');
        }

        $closing = (clone $baseQuery);
        if ($toDate) {
            $closing->whereDate('journals.journal_date', '<=', $toDate);
        }
        $closingTotals = $closing
            ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit) as closing_debit, SUM(journal_lines.credit) as closing_credit')
            ->groupBy('journal_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $accountIds = $totals->keys()->merge($opening->keys())->merge($closingTotals->keys())->unique();
        $accounts = Account::query()->whereIn('id', $accountIds)->orderBy('code')->get()->keyBy('id');

        $rows = $accountIds->map(function ($accountId) use ($accounts, $totals, $opening, $closingTotals) {
            $account = $accounts[$accountId] ?? null;
            if (! $account) {
                return null;
            }

            $periodDebit = (float) ($totals[$accountId]->total_debit ?? 0);
            $periodCredit = (float) ($totals[$accountId]->total_credit ?? 0);
            $openingDebit = (float) ($opening[$accountId]->opening_debit ?? 0);
            $openingCredit = (float) ($opening[$accountId]->opening_credit ?? 0);
            $closingDebit = (float) ($closingTotals[$accountId]->closing_debit ?? 0);
            $closingCredit = (float) ($closingTotals[$accountId]->closing_credit ?? 0);

            $openingBalance = $openingDebit - $openingCredit;
            $closingBalance = $closingDebit - $closingCredit;

            return [
                'account' => $account,
                'opening_debit' => $openingDebit,
                'opening_credit' => $openingCredit,
                'opening_balance' => $openingBalance,
                'period_debit' => $periodDebit,
                'period_credit' => $periodCredit,
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
                'closing_balance' => $closingBalance,
                'debit' => $periodDebit,
                'credit' => $periodCredit,
                'balance' => $closingBalance,
            ];
        })->filter()->values();

        return $rows;
    }

    /**
     * General ledger lines for an account with optional date range and pagination.
     *
     * @return array{account: Account|null, lines: Collection, paginator: LengthAwarePaginator|null}
     */
    public function generalLedger(?int $accountId = null, ?string $fromDate = null, ?string $toDate = null, int $perPage = 50): array
    {
        $account = null;
        if ($accountId) {
            $account = Account::find($accountId);
        }
        if (! $account) {
            $account = Account::query()->orderBy('code')->first();
        }
        if (! $account) {
            return [
                'account' => null,
                'lines' => collect(),
                'paginator' => null,
            ];
        }

        $query = JournalLine::query()
            ->where('journal_lines.account_id', $account->id)
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->select('journal_lines.*');

        if ($fromDate) {
            $query->whereDate('journals.journal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('journals.journal_date', '<=', $toDate);
        }

        $query->orderBy('journals.journal_date')->orderBy('journals.journal_number')->orderBy('journal_lines.id');

        $paginator = $query->with('journal')->paginate($perPage);
        $lines = $paginator->getCollection();

        $openingBalance = $this->openingBalanceForAccount($account->id, $fromDate);
        $runningBalance = $openingBalance;

        $mapped = $lines->map(function (JournalLine $line) use (&$runningBalance) {
            $runningBalance += ((float) $line->debit - (float) $line->credit);
            $journal = $line->journal;

            return [
                'journal_id' => $line->journal_id,
                'date' => $journal?->journal_date,
                'journal_number' => $journal?->journal_number,
                'description' => $line->description ?? $journal?->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => $runningBalance,
            ];
        });

        return [
            'account' => $account,
            'lines' => $mapped,
            'paginator' => $paginator,
            'opening_balance' => $openingBalance,
        ];
    }

    protected function openingBalanceForAccount(int $accountId, ?string $beforeDate): float
    {
        if (! $beforeDate) {
            return 0;
        }
        $row = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereDate('journals.journal_date', '<', $beforeDate)
            ->where('journal_lines.account_id', $accountId)
            ->selectRaw('SUM(journal_lines.debit) - SUM(journal_lines.credit) as balance')
            ->first();

        return (float) ($row->balance ?? 0);
    }

    /**
     * Income statement for date range. Sections from config gl_statements.income_statement.
     *
     * @return array{sections: array, total_revenue: float, total_expense: float, net_income: float, from_date: string, to_date: string}
     */
    public function incomeStatement(string $fromDate, string $toDate): array
    {
        $sections = config('gl_statements.income_statement', []);
        $result = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($sections as $section) {
            $amount = $this->sumByAccountPrefixes($section['account_prefixes'], $fromDate, $toDate);
            $isRevenue = in_array($section['key'], ['revenue', 'other_income'], true);
            if ($isRevenue) {
                $totalRevenue += $amount;
            } else {
                $totalExpense += abs($amount);
            }
            $result[] = [
                'key' => $section['key'],
                'label' => $section['label'],
                'amount' => $amount,
            ];
        }

        return [
            'sections' => $result,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * P&L per Revenue: revenue broken down by segment (config revenue_breakdown) plus cost/expense sections.
     *
     * @return array{revenue_segments: array, expense_sections: array, total_revenue: float, total_expense: float, net_income: float, from_date: string, to_date: string}
     */
    public function plPerRevenue(string $fromDate, string $toDate): array
    {
        $revenueBreakdown = config('gl_statements.revenue_breakdown', []);
        $revenueSegments = [];
        $totalRevenue = 0;
        foreach ($revenueBreakdown as $segment) {
            $amount = $this->sumByAccountPrefixes($segment['account_prefixes'], $fromDate, $toDate);
            $revenueSegments[] = [
                'key' => $segment['key'],
                'label' => $segment['label'],
                'amount' => $amount,
            ];
            $totalRevenue += $amount;
        }

        $expenseKeys = ['cost_of_revenue', 'operating_expenses', 'other_income', 'other_expense'];
        $incomeStatement = config('gl_statements.income_statement', []);
        $expenseSections = [];
        $totalExpense = 0;
        foreach ($incomeStatement as $section) {
            if (in_array($section['key'], $expenseKeys, true)) {
                $amount = $this->sumByAccountPrefixes($section['account_prefixes'], $fromDate, $toDate);
                $isIncome = $section['key'] === 'other_income';
                $value = $isIncome ? $amount : -abs($amount);
                $expenseSections[] = [
                    'key' => $section['key'],
                    'label' => $section['label'],
                    'amount' => $value,
                ];
                $totalExpense += $isIncome ? -$amount : abs($amount);
            }
        }

        return [
            'revenue_segments' => $revenueSegments,
            'expense_sections' => $expenseSections,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Balance sheet as of a given date.
     *
     * @return array{sections: array, total_assets: float, total_liabilities: float, total_equity: float, as_of_date: string}
     */
    public function balanceSheet(string $asOfDate): array
    {
        $sections = config('gl_statements.balance_sheet', []);
        $result = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        $assetKeys = ['current_assets', 'fixed_assets', 'other_assets'];
        $liabilityKeys = ['current_liabilities', 'long_term_liabilities'];

        foreach ($sections as $section) {
            $amount = $this->sumByAccountPrefixesAsOf($section['account_prefixes'], $asOfDate);
            $result[] = ['key' => $section['key'], 'label' => $section['label'], 'amount' => $amount];
            if (in_array($section['key'], $assetKeys, true)) {
                $totalAssets += $amount;
            } elseif (in_array($section['key'], $liabilityKeys, true)) {
                $totalLiabilities += $amount;
            } else {
                $totalEquity += $amount;
            }
        }

        return [
            'sections' => $result,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'as_of_date' => $asOfDate,
        ];
    }

    /**
     * Cash flow statement (indirect method) for date range.
     *
     * @return array{net_income: float, operating: array, investing: float, financing: float, net_change_cash: float, from_date: string, to_date: string}
     */
    public function cashFlowIndirect(string $fromDate, string $toDate): array
    {
        $isData = $this->incomeStatement($fromDate, $toDate);
        $netIncome = $isData['net_income'];

        $adjustments = config('gl_statements.cash_flow_operating_adjustments', []);
        $operating = [];
        $operatingTotal = 0;
        foreach ($adjustments as $adj) {
            $amount = $this->sumByAccountPrefixes($adj['account_prefixes'], $fromDate, $toDate);
            $operating[] = ['label' => $adj['label'], 'amount' => $amount];
            $operatingTotal += $amount;
        }

        $investingPrefixes = config('gl_statements.cash_flow_investing_prefixes', []);
        $financingPrefixes = config('gl_statements.cash_flow_financing_prefixes', []);
        $investing = $this->sumByAccountPrefixes($investingPrefixes, $fromDate, $toDate);
        $financing = $this->sumByAccountPrefixes($financingPrefixes, $fromDate, $toDate);

        $cashPrefixes = ['14'];
        $cashChange = $this->sumByAccountPrefixes($cashPrefixes, $fromDate, $toDate);

        return [
            'net_income' => $netIncome,
            'operating' => $operating,
            'operating_adjustments_total' => $operatingTotal,
            'cash_from_operations' => $netIncome + $operatingTotal,
            'investing' => $investing,
            'financing' => $financing,
            'net_change_cash' => $cashChange,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Income statement by dimension (e.g. client_id, warehouse_id) for date range.
     * Returns one row per dimension value with section amounts and net income.
     *
     * @param  'client_id'|'warehouse_id'|'project_id'  $dimensionColumn
     * @return array{rows: array, from_date: string, to_date: string, dimension: string}
     */
    public function incomeStatementByDimension(string $dimensionColumn, string $fromDate, string $toDate): array
    {
        $allowed = ['client_id', 'warehouse_id', 'project_id'];
        if (! in_array($dimensionColumn, $allowed, true)) {
            return ['rows' => [], 'from_date' => $fromDate, 'to_date' => $toDate, 'dimension' => $dimensionColumn];
        }

        $sections = config('gl_statements.income_statement', []);
        $dimIds = collect();

        foreach ($sections as $section) {
            $byDim = $this->sumByAccountPrefixesGroupedByDimension($section['account_prefixes'], $fromDate, $toDate, $dimensionColumn);
            $dimIds = $dimIds->merge($byDim->keys());
        }

        $dimIds = $dimIds->unique()->filter();
        $rows = [];

        foreach ($dimIds as $dimId) {
            $totalRevenue = 0;
            $totalExpense = 0;
            $sectionAmounts = [];

            foreach ($sections as $section) {
                $byDim = $this->sumByAccountPrefixesGroupedByDimension($section['account_prefixes'], $fromDate, $toDate, $dimensionColumn);
                $amount = (float) ($byDim->get($dimId) ?? 0);
                $sectionAmounts[$section['key']] = ['label' => $section['label'], 'amount' => $amount];

                if (in_array($section['key'], ['revenue', 'other_income'], true)) {
                    $totalRevenue += $amount;
                } else {
                    $totalExpense += abs($amount);
                }
            }

            $rows[] = [
                'dimension_id' => $dimId,
                'sections' => $sectionAmounts,
                'total_revenue' => $totalRevenue,
                'total_expense' => $totalExpense,
                'net_income' => $totalRevenue - $totalExpense,
            ];
        }

        return [
            'rows' => $rows,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'dimension' => $dimensionColumn,
            'section_keys' => collect($sections)->pluck('key')->all(),
        ];
    }

    /**
     * Sum debit - credit for accounts whose code starts with any of the given prefixes (within date range).
     */
    protected function sumByAccountPrefixes(array $prefixes, string $fromDate, string $toDate): float
    {
        if (empty($prefixes)) {
            return 0;
        }
        $accountIds = Account::query()
            ->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('code', 'like', $prefix . '%');
                }
            })
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0;
        }

        $row = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereDate('journals.journal_date', '>=', $fromDate)
            ->whereDate('journals.journal_date', '<=', $toDate)
            ->whereIn('journal_lines.account_id', $accountIds)
            ->selectRaw('SUM(journal_lines.debit) - SUM(journal_lines.credit) as balance')
            ->first();

        return (float) ($row->balance ?? 0);
    }

    /**
     * Sum by account prefixes grouped by dimension column (returns collection keyed by dimension id).
     *
     * @return \Illuminate\Support\Collection<int, float>
     */
    protected function sumByAccountPrefixesGroupedByDimension(array $prefixes, string $fromDate, string $toDate, string $dimensionColumn): \Illuminate\Support\Collection
    {
        if (empty($prefixes)) {
            return collect();
        }
        $accountIds = Account::query()
            ->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('code', 'like', $prefix . '%');
                }
            })
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        $rows = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereDate('journals.journal_date', '>=', $fromDate)
            ->whereDate('journals.journal_date', '<=', $toDate)
            ->whereNotNull('journal_lines.' . $dimensionColumn)
            ->whereIn('journal_lines.account_id', $accountIds)
            ->selectRaw('journal_lines.' . $dimensionColumn . ' as dim_id, SUM(journal_lines.debit) - SUM(journal_lines.credit) as balance')
            ->groupBy('journal_lines.' . $dimensionColumn)
            ->get();

        return $rows->pluck('balance', 'dim_id')->map(fn ($v) => (float) $v);
    }

    /**
     * Sum debit - credit for accounts matching prefixes, as of date (all postings on or before).
     */
    protected function sumByAccountPrefixesAsOf(array $prefixes, string $asOfDate): float
    {
        if (empty($prefixes)) {
            return 0;
        }
        $accountIds = Account::query()
            ->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('code', 'like', $prefix . '%');
                }
            })
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0;
        }

        $row = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereDate('journals.journal_date', '<=', $asOfDate)
            ->whereIn('journal_lines.account_id', $accountIds)
            ->selectRaw('SUM(journal_lines.debit) - SUM(journal_lines.credit) as balance')
            ->first();

        return (float) ($row->balance ?? 0);
    }
}
