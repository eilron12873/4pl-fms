<?php

namespace App\Modules\FinancialReporting\Application;

use App\Modules\GeneralLedger\Application\ReportingService;
use Carbon\Carbon;

class AdvancedReportingService
{
    public function __construct(
        protected ReportingService $reporting
    ) {}

    /**
     * Management summary: income statement for period plus YTD net income and gross margin %.
     *
     * @return array{sections: array, total_revenue: float, total_expense: float, net_income: float, from_date: string, to_date: string, ytd_net_income: float, gross_margin_pct: float|null}
     */
    public function managementSummary(string $fromDate, string $toDate): array
    {
        $is = $this->reporting->incomeStatement($fromDate, $toDate);

        $yearStart = Carbon::parse($toDate)->startOfYear()->toDateString();
        $ytdIs = $this->reporting->incomeStatement($yearStart, $toDate);
        $ytdNetIncome = $ytdIs['net_income'];

        $totalRevenue = (float) ($is['total_revenue'] ?? 0);
        $costOfRevenue = 0;
        foreach ($is['sections'] ?? [] as $section) {
            if (($section['key'] ?? '') === 'cost_of_revenue') {
                $costOfRevenue = abs((float) ($section['amount'] ?? 0));
                break;
            }
        }
        $grossMarginPct = $totalRevenue > 0
            ? round((($totalRevenue - $costOfRevenue) / $totalRevenue) * 100, 2)
            : null;

        return [
            'sections' => $is['sections'] ?? [],
            'total_revenue' => $totalRevenue,
            'total_expense' => (float) ($is['total_expense'] ?? 0),
            'net_income' => (float) ($is['net_income'] ?? 0),
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'ytd_net_income' => (float) $ytdNetIncome,
            'gross_margin_pct' => $grossMarginPct,
        ];
    }

    /**
     * Comparative income statement: current period vs prior period (same length) with variance $ and %.
     *
     * @return array{current: array, prior: array, rows: array, from_date: string, to_date: string, prior_from_date: string, prior_to_date: string}
     */
    public function comparativeIncomeStatement(string $fromDate, string $toDate): array
    {
        $current = $this->reporting->incomeStatement($fromDate, $toDate);

        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);
        $days = $from->diffInDays($to) + 1;
        $priorTo = $from->copy()->subDay();
        $priorFrom = $priorTo->copy()->subDays($days - 1);
        $priorFromStr = $priorFrom->toDateString();
        $priorToStr = $priorTo->toDateString();

        $prior = $this->reporting->incomeStatement($priorFromStr, $priorToStr);

        $currentSections = collect($current['sections'] ?? [])->keyBy('key');
        $priorSections = collect($prior['sections'] ?? [])->keyBy('key');
        $keys = $currentSections->keys()->merge($priorSections->keys())->unique();

        $rows = [];
        foreach ($keys as $key) {
            $cur = $currentSections->get($key);
            $pr = $priorSections->get($key);
            $label = $cur['label'] ?? $pr['label'] ?? $key;
            $curAmt = (float) ($cur['amount'] ?? 0);
            $prAmt = (float) ($pr['amount'] ?? 0);
            $variance = $curAmt - $prAmt;
            $variancePct = $prAmt != 0 ? round(($variance / abs($prAmt)) * 100, 2) : null;

            $rows[] = [
                'key' => $key,
                'label' => $label,
                'current' => $curAmt,
                'prior' => $prAmt,
                'variance' => $variance,
                'variance_pct' => $variancePct,
            ];
        }

        return [
            'current' => $current,
            'prior' => $prior,
            'rows' => $rows,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'prior_from_date' => $priorFromStr,
            'prior_to_date' => $priorToStr,
            'total_revenue_current' => (float) ($current['total_revenue'] ?? 0),
            'total_revenue_prior' => (float) ($prior['total_revenue'] ?? 0),
            'total_expense_current' => (float) ($current['total_expense'] ?? 0),
            'total_expense_prior' => (float) ($prior['total_expense'] ?? 0),
            'net_income_current' => (float) ($current['net_income'] ?? 0),
            'net_income_prior' => (float) ($prior['net_income'] ?? 0),
        ];
    }

    /**
     * Tax summary: revenue and expense totals by section for the period (suitable for tax reporting).
     *
     * @return array{sections: array, total_revenue: float, total_expense: float, net_income: float, from_date: string, to_date: string}
     */
    public function taxSummary(string $fromDate, string $toDate): array
    {
        $is = $this->reporting->incomeStatement($fromDate, $toDate);

        $sections = [];
        foreach ($is['sections'] ?? [] as $section) {
            $amount = (float) ($section['amount'] ?? 0);
            $sections[] = [
                'key' => $section['key'],
                'label' => $section['label'],
                'amount' => $amount,
                'is_revenue' => in_array($section['key'], ['revenue', 'other_income'], true),
            ];
        }

        return [
            'sections' => $sections,
            'total_revenue' => (float) ($is['total_revenue'] ?? 0),
            'total_expense' => (float) ($is['total_expense'] ?? 0),
            'net_income' => (float) ($is['net_income'] ?? 0),
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }
}
