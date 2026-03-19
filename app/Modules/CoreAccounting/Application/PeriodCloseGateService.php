<?php

namespace App\Modules\CoreAccounting\Application;

use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;

class PeriodCloseGateService
{
    /**
     * @return array{0:bool,1:array<int,array<string,mixed>>}
     */
    public function runChecks(Period $period): array
    {
        $from = $period->start_date?->startOfDay();
        $to = $period->end_date?->endOfDay();

        $unbalancedCount = Journal::query()
            ->select('journals.id')
            ->join('journal_lines', 'journals.id', '=', 'journal_lines.journal_id')
            ->whereBetween('journals.journal_date', [$from, $to])
            ->groupBy('journals.id')
            ->havingRaw('ROUND(COALESCE(SUM(journal_lines.debit), 0), 2) <> ROUND(COALESCE(SUM(journal_lines.credit), 0), 2)')
            ->get()
            ->count();

        $integrationErrorsCount = IntegrationLog::where('status', IntegrationLog::STATUS_ERROR)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $checks = [
            [
                'code' => 'journals_balanced',
                'passed' => $unbalancedCount === 0,
                'message' => $unbalancedCount === 0 ? 'No unbalanced journals found.' : "{$unbalancedCount} unbalanced journals found.",
            ],
            [
                'code' => 'integration_errors_cleared',
                'passed' => $integrationErrorsCount === 0,
                'message' => $integrationErrorsCount === 0 ? 'No integration posting errors found.' : "{$integrationErrorsCount} integration errors found.",
            ],
        ];

        $allPassed = collect($checks)->every(fn (array $check) => (bool) $check['passed']);

        return [$allPassed, $checks];
    }
}

