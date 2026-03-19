<?php

namespace App\Console\Commands;

use App\Modules\CoreAccounting\Application\PeriodCloseGateService;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\CoreAccounting\Infrastructure\Models\PeriodChangeLog;
use App\Modules\CoreAccounting\Infrastructure\Models\PeriodCloseEvidence;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleAuditLog;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleVersion;
use App\Modules\CoreAccounting\UI\Controllers\CoreAccountingController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class CoreAccountingSmokeRun extends Command
{
    protected $signature = 'core-accounting:smoke-run';

    protected $description = 'Quick smoke checks for posting rules and period close/reopen flow.';

    public function handle(CoreAccountingController $controller, PeriodCloseGateService $periodCloseGate): int
    {
        $this->info('Running Core Accounting smoke run...');

        // 1) Posting rule create/update -> version + audit validation.
        $eventType = 'smoke-rule-' . time();
        $accountIds = Account::where('is_posting', true)->orderBy('id')->limit(2)->pluck('id')->values();
        if ($accountIds->count() < 2) {
            $this->error('Posting rule smoke failed: not enough posting accounts.');
            return self::FAILURE;
        }

        $storeReq = Request::create('/core-accounting/posting-rules', 'POST', [
            'event_type' => $eventType,
            'description' => 'Smoke create',
            'is_active' => false,
            'lines' => [
                ['account_id' => $accountIds[0], 'entry_type' => 'debit', 'amount_source' => 'amount'],
                ['account_id' => $accountIds[1], 'entry_type' => 'credit', 'amount_source' => 'amount'],
            ],
            'conditions' => [],
        ]);
        $controller->postingRulesStore($storeReq);

        $rule = PostingRule::where('event_type', $eventType)->first();
        if (! $rule) {
            $this->error('Posting rule smoke failed: create did not persist.');
            return self::FAILURE;
        }

        $updateReq = Request::create('/core-accounting/posting-rules/' . $rule->id, 'PUT', [
            'event_type' => $eventType,
            'description' => 'Smoke update',
            'is_active' => true,
            'lines' => [
                ['account_id' => $accountIds[0], 'entry_type' => 'debit', 'amount_source' => 'amount'],
                ['account_id' => $accountIds[1], 'entry_type' => 'credit', 'amount_source' => 'amount'],
            ],
            'conditions' => [
                ['field_name' => 'service_line', 'operator' => '=', 'comparison_value' => 'transport'],
            ],
        ]);
        $controller->postingRulesUpdate($updateReq, (int) $rule->id);

        $versionCount = PostingRuleVersion::where('posting_rule_id', $rule->id)->count();
        $auditActions = PostingRuleAuditLog::where('posting_rule_id', $rule->id)->pluck('action')->all();

        $this->line("Posting rule smoke: rule_id={$rule->id}, versions={$versionCount}, audits=" . implode(',', $auditActions));

        if ($versionCount < 2 || ! in_array('created', $auditActions, true) || ! in_array('updated', $auditActions, true)) {
            $this->error('Posting rule smoke failed: version/audit rows are incomplete.');
            return self::FAILURE;
        }

        // 2) Period close/reopen -> evidence + reason logging validation.
        $period = Period::create([
            'code' => 'SMK-' . date('Ym') . '-' . random_int(100, 999),
            'start_date' => now()->addYear()->startOfMonth()->toDateString(),
            'end_date' => now()->addYear()->endOfMonth()->toDateString(),
            'status' => 'open',
            'closed_at' => null,
        ]);

        [$passed, $checks] = $periodCloseGate->runChecks($period);
        PeriodCloseEvidence::create([
            'period_id' => $period->id,
            'created_by' => null,
            'checks' => $checks,
            'metadata' => ['action' => 'close'],
        ]);

        if (! $passed) {
            $this->error('Period smoke failed: pre-close checks did not pass.');
            return self::FAILURE;
        }

        $period->update(['status' => 'closed', 'closed_at' => now()]);
        PeriodChangeLog::create(['period_id' => $period->id, 'action' => 'closed', 'user_id' => null]);

        $reopenReason = 'Smoke run reopen verification';
        $period->update(['status' => 'open', 'closed_at' => null]);
        PeriodChangeLog::create(['period_id' => $period->id, 'action' => 'reopened', 'user_id' => null]);
        PeriodCloseEvidence::create([
            'period_id' => $period->id,
            'created_by' => null,
            'checks' => [['code' => 'reopen_reason', 'passed' => true, 'message' => $reopenReason]],
            'metadata' => ['action' => 'reopen', 'reason' => $reopenReason],
        ]);

        $evidenceCount = PeriodCloseEvidence::where('period_id', $period->id)->count();
        $reopenedCount = PeriodChangeLog::where('period_id', $period->id)->where('action', 'reopened')->count();
        $reasonEvidence = PeriodCloseEvidence::where('period_id', $period->id)
            ->where('metadata->action', 'reopen')
            ->where('metadata->reason', $reopenReason)
            ->exists();

        $this->line("Period smoke: period_id={$period->id}, evidences={$evidenceCount}, reopened_logs={$reopenedCount}, reason_logged=" . ($reasonEvidence ? 'yes' : 'no'));

        if ($evidenceCount < 2 || $reopenedCount < 1 || ! $reasonEvidence) {
            $this->error('Period smoke failed: evidence/reason logging incomplete.');
            return self::FAILURE;
        }

        $this->info('Smoke run passed.');

        return self::SUCCESS;
    }
}

