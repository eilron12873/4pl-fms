<?php

namespace App\Modules\CoreAccounting\Application;

use App\Core\Services\SystemSettingsService;
use Carbon\Carbon;
use InvalidArgumentException;

class FinancialControlsGate
{
    public function __construct(
        protected SystemSettingsService $systemSettings
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function assertPostingAllowed(string $journalDate, array $meta): void
    {
        $this->assertBackdatingAllowed($journalDate);

        if ($this->isManualPosting($meta) && ! $this->systemSettings->financialControls()->allow_manual_journals) {
            throw new InvalidArgumentException('Manual journals are disabled in Financial Controls.');
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function isManualPosting(array $meta): bool
    {
        if (($meta['journal_origin'] ?? '') === 'reversal') {
            return false;
        }

        return ! isset($meta['source_system']);
    }

    protected function assertBackdatingAllowed(string $journalDate): void
    {
        $maxDays = $this->systemSettings->financialControls()->max_backdating_days;
        if ($maxDays === null) {
            return;
        }

        $date = Carbon::parse($journalDate)->startOfDay();
        $min = now()->startOfDay()->subDays((int) $maxDays);

        if ($date->lt($min)) {
            throw new InvalidArgumentException(
                "Journal date {$journalDate} is outside the allowed backdating window (max {$maxDays} days)."
            );
        }
    }
}
