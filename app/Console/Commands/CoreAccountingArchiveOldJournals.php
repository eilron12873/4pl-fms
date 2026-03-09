<?php

namespace App\Console\Commands;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CoreAccountingArchiveOldJournals extends Command
{
    protected $signature = 'core-accounting:archive-journals {--days=1825 : Archive journals older than this many days}';

    protected $description = 'Archive old journals and journal lines into archive tables for long-term storage.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days)->toDateString();

        $this->info("Archiving journals with journal_date <= {$cutoff}...");

        DB::transaction(function () use ($cutoff) {
            $journals = Journal::whereDate('journal_date', '<=', $cutoff)->get();

            foreach ($journals as $journal) {
                DB::table('archived_journals')->insert([
                    'journal_id' => $journal->id,
                    'journal_number' => $journal->journal_number,
                    'journal_date' => $journal->journal_date,
                    'period' => $journal->period,
                    'description' => $journal->description,
                    'status' => $journal->status,
                    'posted_at' => $journal->posted_at,
                    'created_at' => $journal->created_at,
                    'updated_at' => $journal->updated_at,
                ]);

                $lines = $journal->lines()->get();
                foreach ($lines as $line) {
                    DB::table('archived_journal_lines')->insert([
                        'journal_id' => $journal->id,
                        'account_id' => $line->account_id,
                        'description' => $line->description,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'client_id' => $line->client_id,
                        'shipment_id' => $line->shipment_id,
                        'route_id' => $line->route_id,
                        'warehouse_id' => $line->warehouse_id,
                        'vehicle_id' => $line->vehicle_id,
                        'project_id' => $line->project_id,
                        'service_line_id' => $line->service_line_id,
                        'cost_center_id' => $line->cost_center_id,
                        'created_at' => $line->created_at,
                        'updated_at' => $line->updated_at,
                    ]);
                }

                $journal->lines()->delete();
                $journal->delete();
            }
        });

        $this->info('Archiving completed.');

        return self::SUCCESS;
    }
}

