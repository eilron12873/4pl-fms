<?php

namespace App\Console\Commands;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Console\Command;

class CoreAccountingPrunePostingPayloads extends Command
{
    protected $signature = 'core-accounting:prune-posting-payloads {--days=365 : Null out posting payloads older than this many days}';

    protected $description = 'Prune old posting source payloads to reduce storage, keeping header metadata for traceability.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $this->info("Pruning posting_sources payloads created before {$cutoff->toDateTimeString()}...");

        PostingSource::where('created_at', '<', $cutoff)
            ->whereNotNull('payload')
            ->update(['payload' => null]);

        $this->info('Pruning completed.');

        return self::SUCCESS;
    }
}

