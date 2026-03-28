<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

class AuditPruneActivities extends Command
{
    protected $signature = 'audit:prune-activities
                            {--dry-run : Report how many rows would be deleted without deleting}
                            {--days= : Override retention days from config}';

    protected $description = 'Prune activity log rows older than the configured retention (off unless AUDIT_PRUNE_ENABLED is true).';

    public function handle(): int
    {
        if (! config('audit.prune.enabled')) {
            $this->warn('Audit pruning is disabled. Set AUDIT_PRUNE_ENABLED=true to enable deletes.');

            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?: config('audit.prune.default_retention_days', 2555));
        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $count = Activity::query()->where('created_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('No activities older than the retention window.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Would delete {$count} activities with created_at before {$cutoff->toIso8601String()} (retention: {$days} days).");

            return self::SUCCESS;
        }

        $deleted = 0;
        do {
            $ids = Activity::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(1000)
                ->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $n = Activity::query()->whereIn('id', $ids)->delete();
            $deleted += $n;
        } while (true);

        $this->info("Deleted {$deleted} activities.");

        return self::SUCCESS;
    }
}
