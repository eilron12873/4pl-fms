<?php

namespace App\Modules\CoreAccounting\Application;

use App\Core\Services\AuditService;
use App\Modules\CoreAccounting\Domain\Exceptions\JournalImmutableException;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Infrastructure\Models\ReversalLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JournalService
{
    public function __construct(
        protected AuditService $audit
    ) {}

    /**
     * Post a journal with the given lines and metadata.
     * Enforces: double-entry (debit = credit), period locking, immutable ledger (no in-place edits).
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $meta
     */
    public function post(array $lines, array $meta = []): Journal
    {
        if (empty($lines)) {
            throw new InvalidArgumentException('Journal must contain at least one line.');
        }

        $this->validateBalanced($lines);

        $journalDate = $meta['journal_date'] ?? now()->toDateString();
        $this->assertPeriodOpenForDate($journalDate);

        return DB::transaction(function () use ($lines, $meta, $journalDate) {
            $journalNumber = $meta['journal_number'] ?? Str::uuid()->toString();
            $periodCode = $meta['period'] ?? $this->resolvePeriodCodeForDate($journalDate);

            $journal = Journal::create([
                'journal_number' => $journalNumber,
                'journal_date' => $journalDate,
                'period' => $periodCode,
                'description' => $meta['description'] ?? null,
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $this->createJournalLine($journal, $line);
            }

            if (isset($meta['source_system'], $meta['source_reference'], $meta['idempotency_key'])) {
                PostingSource::create([
                    'journal_id' => $journal->id,
                    'source_system' => $meta['source_system'],
                    'source_type' => $meta['source_type'] ?? null,
                    'source_reference' => $meta['source_reference'],
                    'event_type' => $meta['event_type'] ?? null,
                    'idempotency_key' => $meta['idempotency_key'],
                    'payload' => $meta['payload'] ?? null,
                ]);
            }

            $this->audit->logFinancial(
                "Journal posted: {$journal->journal_number} ({$journal->journal_date})",
                $journal,
                ['journal_number' => $journal->journal_number, 'period' => $periodCode],
                'journal.posted',
            );

            return $journal;
        });
    }

    /**
     * Create a reversal journal for the given journal. Enforces immutable ledger: corrections via reversal only.
     *
     * @param  array<string, mixed>  $meta
     */
    public function reversal(Journal $journal, array $meta = []): Journal
    {
        if ($journal->status !== 'posted') {
            throw new InvalidArgumentException('Only posted journals can be reversed.');
        }

        $reversalDate = $meta['journal_date'] ?? now()->toDateString();
        $this->assertPeriodOpenForDate($reversalDate);

        $lines = $journal->lines()->get();
        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('Journal has no lines to reverse.');
        }

        $reversalLines = [];
        foreach ($lines as $line) {
            $reversalLines[] = [
                'account_id' => $line->account_id,
                'description' => $line->description,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'client_id' => $line->client_id,
                'shipment_id' => $line->shipment_id,
                'route_id' => $line->route_id,
                'warehouse_id' => $line->warehouse_id,
                'vehicle_id' => $line->vehicle_id,
                'project_id' => $line->project_id,
                'service_line_id' => $line->service_line_id,
                'cost_center_id' => $line->cost_center_id,
            ];
        }

        $reversalMeta = array_merge($meta, [
            'journal_date' => $reversalDate,
            'description' => ($meta['description'] ?? 'Reversal') . ' of ' . $journal->journal_number,
            'period' => $meta['period'] ?? $this->resolvePeriodCodeForDate($reversalDate),
        ]);

        $reversalJournal = $this->post($reversalLines, $reversalMeta);

        ReversalLink::create([
            'original_journal_id' => $journal->id,
            'reversal_journal_id' => $reversalJournal->id,
        ]);

        $this->audit->logFinancial(
            "Journal reversed: {$journal->journal_number} -> {$reversalJournal->journal_number}",
            $reversalJournal,
            ['original_id' => $journal->id, 'reversal_id' => $reversalJournal->id],
            'journal.reversed',
        );

        return $reversalJournal;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function validateBalanced(array $lines): void
    {
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException('Journal is not balanced (debit != credit).');
        }
    }

    protected function assertPeriodOpenForDate(string $date): void
    {
        $period = Period::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $period) {
            return;
        }

        if (! $period->isOpen()) {
            throw new PeriodLockedException(
                "Posting is not allowed: period {$period->code} ({$period->start_date->toDateString()} to {$period->end_date->toDateString()}) is closed."
            );
        }
    }

    protected function resolvePeriodCodeForDate(string $date): ?string
    {
        $period = Period::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        return $period?->code;
    }

    protected function createJournalLine(Journal $journal, array $line): void
    {
        $account = $this->resolveAccount($line);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $account->id,
            'description' => $line['description'] ?? null,
            'debit' => $line['debit'] ?? 0,
            'credit' => $line['credit'] ?? 0,
            'client_id' => $line['client_id'] ?? null,
            'shipment_id' => $line['shipment_id'] ?? null,
            'route_id' => $line['route_id'] ?? null,
            'warehouse_id' => $line['warehouse_id'] ?? null,
            'vehicle_id' => $line['vehicle_id'] ?? null,
            'project_id' => $line['project_id'] ?? null,
            'service_line_id' => $line['service_line_id'] ?? null,
            'cost_center_id' => $line['cost_center_id'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected function resolveAccount(array $line): Account
    {
        if (isset($line['account_id'])) {
            return Account::findOrFail($line['account_id']);
        }

        if (! isset($line['account_code'])) {
            throw new InvalidArgumentException('Each journal line must have account_id or account_code.');
        }

        $code = (string) $line['account_code'];

        $type = $line['account_type'] ?? $this->guessAccountType($code);

        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => $line['account_name'] ?? $code,
                'type' => $type,
                'level' => 1,
                'is_posting' => true,
            ],
        );
    }

    protected function guessAccountType(string $code): string
    {
        if (Str::startsWith($code, '1')) {
            return 'asset';
        }
        if (Str::startsWith($code, '2')) {
            return 'liability';
        }
        if (Str::startsWith($code, '3')) {
            return 'equity';
        }
        if (Str::startsWith($code, '4')) {
            return 'revenue';
        }
        if (Str::startsWith($code, '5')) {
            return 'expense';
        }

        return 'asset';
    }
}

