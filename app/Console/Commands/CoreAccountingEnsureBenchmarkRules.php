<?php

namespace App\Console\Commands;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleLine;
use Illuminate\Console\Command;

class CoreAccountingEnsureBenchmarkRules extends Command
{
    protected $signature = 'core-accounting:ensure-benchmark-rules';

    protected $description = 'Ensure minimal active posting rules with lines exist for key benchmark events.';

    public function handle(): int
    {
        $this->ensureRule('client-invoice-issued', 'Client invoice issued (benchmark)', '121100', 'Trade Receivables', 'asset', '423000', 'Freight Revenue', 'revenue');
        $this->ensureRule('client-payment-received', 'Client payment received (benchmark)', '112100', 'Cash in Bank - BDO', 'asset', '121100', 'Trade Receivables', 'asset');
        $this->ensureRule('pod-confirmed', 'POD confirmed (benchmark)', '121100', 'Trade Receivables', 'asset', '422000', 'Delivery Revenue', 'revenue');

        $this->info('Benchmark rules ensured.');

        return self::SUCCESS;
    }

    protected function ensureRule(
        string $eventType,
        string $description,
        string $debitCode,
        string $debitName,
        string $debitType,
        string $creditCode,
        string $creditName,
        string $creditType
    ): void {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => $eventType],
            [
                'description' => $description,
                'is_active' => true,
            ],
        );

        $rule->update([
            'description' => $description,
            'is_active' => true,
        ]);

        if (! $rule->lines()->exists()) {
            $debitAccountId = $this->accountId($debitCode, $debitName, $debitType);
            $creditAccountId = $this->accountId($creditCode, $creditName, $creditType);

            PostingRuleLine::create([
                'posting_rule_id' => $rule->id,
                'account_id' => $debitAccountId,
                'entry_type' => 'debit',
                'amount_source' => 'amount',
                'dimension_source' => null,
                'sequence' => 1,
            ]);

            PostingRuleLine::create([
                'posting_rule_id' => $rule->id,
                'account_id' => $creditAccountId,
                'entry_type' => 'credit',
                'amount_source' => 'amount',
                'dimension_source' => null,
                'sequence' => 2,
            ]);
        }
    }

    protected function accountId(string $code, string $name, string $type): int
    {
        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'level' => 2,
                'is_posting' => true,
            ],
        )->id;
    }
}

