<?php

namespace App\Modules\Treasury\Application;

use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use Illuminate\Support\Collection;

class TreasuryService
{
    public function createAccount(array $data): BankAccount
    {
        $data['opened_at'] = $data['opened_at'] ?? now()->toDateString();
        return BankAccount::create($data);
    }

    public function recordTransaction(
        int $bankAccountId,
        string $transactionDate,
        string $description,
        float $amount,
        string $type = 'deposit',
        ?string $reference = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): BankTransaction {
        return BankTransaction::create([
            'bank_account_id' => $bankAccountId,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'amount' => $amount,
            'reference' => $reference,
            'type' => $type,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    /**
     * @return array{accounts: Collection, total_by_currency: array<string, float>}
     */
    public function cashPosition(): array
    {
        $accounts = BankAccount::where('is_active', true)
            ->withSum('transactions', 'amount')
            ->orderBy('name')
            ->get();

        $totalByCurrency = [];
        foreach ($accounts as $account) {
            $balance = (float) $account->opening_balance + (float) ($account->transactions_sum_amount ?? 0);
            $totalByCurrency[$account->currency] = ($totalByCurrency[$account->currency] ?? 0) + $balance;
        }

        return [
            'accounts' => $accounts,
            'total_by_currency' => $totalByCurrency,
        ];
    }

    public function getAccountBalance(BankAccount $account): float
    {
        $sum = $account->transactions()->sum('amount');
        return (float) $account->opening_balance + (float) $sum;
    }

    /**
     * Match a statement line to a transaction and mark both as reconciled.
     */
    public function matchStatementLineToTransaction(BankStatementLine $statementLine, BankTransaction $transaction): void
    {
        if ($statementLine->bank_account_id !== $transaction->bank_account_id) {
            throw new \InvalidArgumentException('Statement line and transaction must belong to the same bank account.');
        }
        if ($statementLine->isMatched()) {
            throw new \InvalidArgumentException('Statement line is already matched.');
        }
        if ($transaction->isReconciled()) {
            throw new \InvalidArgumentException('Transaction is already reconciled.');
        }

        $statementLine->update([
            'bank_transaction_id' => $transaction->id,
            'matched_at' => now(),
        ]);
        $transaction->update(['reconciled_at' => now()]);
    }

    /**
     * Unmatch a statement line from its transaction.
     */
    public function unmatchStatementLine(BankStatementLine $statementLine): void
    {
        if (! $statementLine->isMatched()) {
            return;
        }
        $transaction = $statementLine->bankTransaction;
        $statementLine->update(['bank_transaction_id' => null, 'matched_at' => null]);
        if ($transaction) {
            $transaction->update(['reconciled_at' => null]);
        }
    }

    public function addStatementLine(
        int $bankAccountId,
        string $statementDate,
        float $amount,
        ?string $description = null,
        ?string $reference = null,
        ?string $bankSequence = null,
    ): BankStatementLine {
        return BankStatementLine::create([
            'bank_account_id' => $bankAccountId,
            'statement_date' => $statementDate,
            'description' => $description,
            'amount' => $amount,
            'reference' => $reference,
            'bank_sequence' => $bankSequence,
        ]);
    }
}
