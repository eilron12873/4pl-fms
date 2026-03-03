<?php

namespace App\Modules\CoreAccounting\Application;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JournalService
{
    /**
     * Post a journal with the given lines and metadata.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $meta
     */
    public function post(array $lines, array $meta = []): Journal
    {
        if (empty($lines)) {
            throw new InvalidArgumentException('Journal must contain at least one line.');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException('Journal is not balanced (debit != credit).');
        }

        return DB::transaction(function () use ($lines, $meta) {
            $journalNumber = $meta['journal_number'] ?? Str::uuid()->toString();
            $journalDate = $meta['journal_date'] ?? now()->toDateString();

            $journal = Journal::create([
                'journal_number' => $journalNumber,
                'journal_date' => $journalDate,
                'period' => $meta['period'] ?? null,
                'description' => $meta['description'] ?? null,
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
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

            return $journal;
        });
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

