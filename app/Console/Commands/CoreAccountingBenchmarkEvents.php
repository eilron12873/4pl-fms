<?php

namespace App\Console\Commands;

use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CoreAccountingBenchmarkEvents extends Command
{
    protected $signature = 'core-accounting:benchmark-events
        {event_type : Financial event type to benchmark}
        {--count=1000 : Number of events to execute}
        {--duplicate-rate=0 : Percentage (0-100) of duplicate idempotency keys}
        {--source-system=benchmark : Source system label}
        {--dry-run : Validate command flow without dispatching events}';

    protected $description = 'Benchmark financial event posting throughput and duplicate handling.';

    public function handle(FinancialEventDispatcher $dispatcher): int
    {
        $eventType = (string) $this->argument('event_type');
        $count = max(1, (int) $this->option('count'));
        $duplicateRate = min(100, max(0, (int) $this->option('duplicate-rate')));
        $dryRun = (bool) $this->option('dry-run');
        $sourceSystem = (string) $this->option('source-system');

        $this->info("Starting benchmark: {$eventType}, count={$count}, duplicate-rate={$duplicateRate}%");
        $started = microtime(true);

        $duplicates = 0;
        $posted = 0;
        $errors = 0;
        $firstErrorClass = null;
        $firstErrorMessage = null;
        $firstKey = 'bench-' . Str::uuid();

        for ($i = 1; $i <= $count; $i++) {
            $isDuplicate = $i > 1 && random_int(1, 100) <= $duplicateRate;
            $idempotencyKey = $isDuplicate ? $firstKey : ('bench-' . Str::uuid());
            if ($isDuplicate) {
                $duplicates++;
            }

            if ($dryRun) {
                continue;
            }

            try {
                $dispatcher->dispatch($eventType, [
                    'journal_date' => now()->toDateString(),
                    'amount' => 100,
                ], [
                    'idempotency_key' => $idempotencyKey,
                    'source_system' => $sourceSystem,
                    'source_reference' => "benchmark-{$i}",
                ]);
                $posted++;
            } catch (\Throwable $e) {
                $errors++;
                if ($firstErrorClass === null) {
                    $firstErrorClass = $e::class;
                    $firstErrorMessage = $e->getMessage();
                }
            }
        }

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        $throughput = $elapsedMs > 0 ? round(($count / $elapsedMs) * 1000, 2) : 0;

        $this->table(['Metric', 'Value'], [
            ['events', $count],
            ['posted_attempts', $posted],
            ['duplicate_inputs', $duplicates],
            ['errors', $errors],
            ['elapsed_ms', $elapsedMs],
            ['throughput_events_per_sec', $throughput],
            ['dry_run', $dryRun ? 'yes' : 'no'],
        ]);

        if ($errors > 0 && $firstErrorClass !== null) {
            $this->line('First error class: ' . $firstErrorClass);
            $this->line('First error message: ' . $firstErrorMessage);
        }

        return self::SUCCESS;
    }
}

