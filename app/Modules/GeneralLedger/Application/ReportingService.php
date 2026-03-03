<?php

namespace App\Modules\GeneralLedger\Application;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use Illuminate\Support\Collection;

class ReportingService
{
    /**
     * Build a simple trial balance from posted journals.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function trialBalance(): Collection
    {
        $totals = JournalLine::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereHas('journal', function ($query) {
                $query->where('status', 'posted');
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $accounts = Account::query()
            ->whereIn('id', $totals->keys())
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        return $totals->map(function ($row, $accountId) use ($accounts) {
            $account = $accounts[$accountId];

            $debit = (float) $row->total_debit;
            $credit = (float) $row->total_credit;
            $balance = $debit - $credit;

            return [
                'account' => $account,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        })->values();
    }

    /**
     * Fetch general ledger lines for a single account.
     *
     * @return array<string, mixed>
     */
    public function generalLedger(?int $accountId = null): array
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
            ];
        }

        $lines = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journal', function ($query) {
                $query->where('status', 'posted');
            })
            ->with(['journal'])
            ->orderByRelation('journal', 'journal_date')
            ->orderByRelation('journal', 'journal_number')
            ->orderBy('id')
            ->get();

        $runningBalance = 0;

        $lines = $lines->map(function (JournalLine $line) use (&$runningBalance) {
            $runningBalance += ((float) $line->debit - (float) $line->credit);

            return [
                'date' => $line->journal->journal_date,
                'journal_number' => $line->journal->journal_number,
                'description' => $line->description ?? $line->journal->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => $runningBalance,
            ];
        });

        return [
            'account' => $account,
            'lines' => $lines,
        ];
    }
}

