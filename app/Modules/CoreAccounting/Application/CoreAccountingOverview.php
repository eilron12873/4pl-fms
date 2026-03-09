<?php

namespace App\Modules\CoreAccounting\Application;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;

class CoreAccountingOverview
{
    /**
     * Return a lightweight JSON-serializable overview of Core Accounting state.
     *
     * This is a read-only "documentation hook" for dashboards and health checks
     * and does not change any posting behavior.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $latestJournal = Journal::orderByDesc('journal_date')->orderByDesc('id')->first();

        return [
            'journals' => [
                'total' => Journal::count(),
                'last_journal_date' => $latestJournal?->journal_date?->toDateString(),
                'last_journal_number' => $latestJournal?->journal_number,
            ],
            'periods' => [
                'open' => Period::where('status', 'open')->count(),
                'closed' => Period::where('status', 'closed')->count(),
            ],
            'posting_sources' => [
                'total' => PostingSource::count(),
            ],
        ];
    }
}

