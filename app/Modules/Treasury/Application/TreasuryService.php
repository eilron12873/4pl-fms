<?php

namespace App\Modules\Treasury\Application;

use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;
use Illuminate\Database\QueryException;

class TreasuryService
{
    public function __construct(
        protected JournalService $journalService,
    ) {
    }

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
        ?string $counterpartyGlAccountCode = null,
    ): BankTransaction {
        // Enforce core invariants for amounts to avoid silent sign mistakes.
        if ($type === 'deposit' && $amount <= 0) {
            throw new InvalidArgumentException('Deposit amount must be positive.');
        }
        if ($type === 'withdrawal' && $amount >= 0) {
            throw new InvalidArgumentException('Withdrawal amount must be negative.');
        }
        if ($type === 'fee' && $amount >= 0) {
            throw new InvalidArgumentException('Fee amount must be negative.');
        }
        if ($type === 'adjustment' && abs($amount) < 0.00001) {
            throw new InvalidArgumentException('Adjustment amount cannot be zero.');
        }
        if ($type === 'transfer') {
            throw new InvalidArgumentException('Transfer transactions must be recorded via recordTransfer().');
        }

        $absAmount = round(abs($amount), 2);
        $amountRounded = round($amount, 2);
        $referenceKey = $reference ?? '';
        $idempotencyKeyBase = implode('|', [
            $bankAccountId,
            $transactionDate,
            $type,
            $description,
            (string) $amountRounded,
            $referenceKey,
            (string) ($sourceType ?? ''),
            (string) ($sourceId ?? ''),
        ]);
        $idempotencyKey = 'treasury-tx-' . sha1($idempotencyKeyBase);

        return DB::transaction(function () use (
            $bankAccountId,
            $transactionDate,
            $description,
            $amount,
            $type,
            $reference,
            $sourceType,
            $sourceId,
            $counterpartyGlAccountCode,
            $absAmount,
            $idempotencyKey
        ) {
            $bankTx = BankTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $bankTx) {
                try {
                    $bankTx = BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'transaction_date' => $transactionDate,
                        'description' => $description,
                        'amount' => $amount,
                        'reference' => $reference,
                        'type' => $type,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'idempotency_key' => $idempotencyKey,
                    ]);
                } catch (QueryException) {
                    // Another concurrent request inserted first; re-fetch by idempotency key.
                    $bankTx = BankTransaction::query()
                        ->where('idempotency_key', $idempotencyKey)
                        ->firstOrFail();
                }
            }

            // Idempotent GL posting via PostingSource idempotency.
            $postingIdempotencyKey = 'treasury-journal-' . $idempotencyKey;
            if (! PostingSource::where('idempotency_key', $postingIdempotencyKey)->exists()) {
                $cashAccountCode = BankAccount::query()
                    ->where('id', $bankAccountId)
                    ->value('gl_account_code');

                if (! $cashAccountCode) {
                    throw new InvalidArgumentException('Bank account GL code is missing.');
                }

                [$debitLine, $creditLine] = $this->journalLinesForSingleTransaction(
                    type: $type,
                    amount: $amount,
                    absAmount: $absAmount,
                    cashAccountCode: (string) $cashAccountCode,
                    counterpartyGlAccountCode: $counterpartyGlAccountCode,
                );

                $this->journalService->post(
                    [$debitLine, $creditLine],
                    [
                        'journal_date' => $transactionDate,
                        'description' => 'Treasury ' . $type . ' ' . $description,
                        'source_system' => 'treasury',
                        'source_type' => $type,
                        'source_reference' => (string) $bankTx->id,
                        'event_type' => 'treasury-transaction',
                        'idempotency_key' => $postingIdempotencyKey,
                        'payload' => [
                            'reference' => $reference,
                            'counterparty_gl_account_code' => $counterpartyGlAccountCode,
                            'type' => $type,
                            'amount' => $amount,
                            'transaction_date' => $transactionDate,
                        ],
                    ]
                );
            }

            return $bankTx;
        });
    }

    /**
     * Record a dedicated transfer wrapper as two legs in a single transaction:
     * - transfer_out: origin account decreases (negative amount)
     * - transfer_in: destination account increases (positive amount)
     *
     * @return array{out: BankTransaction, in: BankTransaction}
     */
    public function recordTransfer(
        int $fromBankAccountId,
        int $toBankAccountId,
        string $transactionDate,
        string $description,
        float $amount,
        ?string $reference = null
    ): array {
        if ($fromBankAccountId === $toBankAccountId) {
            throw new InvalidArgumentException('Transfer destination must be different from origin.');
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('Transfer amount must be positive.');
        }

        $amount = round($amount, 2);
        $referenceKey = $reference ?? '';

        $groupBase = implode('|', [
            $fromBankAccountId,
            $toBankAccountId,
            $transactionDate,
            (string) $amount,
            $description,
            $referenceKey,
        ]);
        $groupHash = sha1($groupBase);

        // Same group reference for both legs for reporting/audit.
        $transferGroupReference = $referenceKey !== '' ? $referenceKey : ('TRF-' . substr($groupHash, 0, 10));

        $outIdempotencyKey = 'trf-' . $transferGroupReference . '-' . $fromBankAccountId . '-out';
        $inIdempotencyKey = 'trf-' . $transferGroupReference . '-' . $toBankAccountId . '-in';

        return DB::transaction(function () use (
            $fromBankAccountId,
            $toBankAccountId,
            $transactionDate,
            $description,
            $amount,
            $transferGroupReference,
            $outIdempotencyKey,
            $inIdempotencyKey,
            $reference
        ) {
            $out = BankTransaction::query()->where('idempotency_key', $outIdempotencyKey)->first();
            $in = BankTransaction::query()->where('idempotency_key', $inIdempotencyKey)->first();

            if (! $out) {
                try {
                    $out = BankTransaction::create([
                        'bank_account_id' => $fromBankAccountId,
                        'transaction_date' => $transactionDate,
                        'description' => $description,
                        'amount' => -$amount,
                        'reference' => $reference,
                        'type' => 'transfer_out',
                        'transfer_group_reference' => $transferGroupReference,
                        'idempotency_key' => $outIdempotencyKey,
                    ]);
                } catch (QueryException) {
                    $out = BankTransaction::query()
                        ->where('idempotency_key', $outIdempotencyKey)
                        ->firstOrFail();
                }
            }

            if (! $in) {
                try {
                    $in = BankTransaction::create([
                        'bank_account_id' => $toBankAccountId,
                        'transaction_date' => $transactionDate,
                        'description' => $description,
                        'amount' => $amount,
                        'reference' => $reference,
                        'type' => 'transfer_in',
                        'transfer_group_reference' => $transferGroupReference,
                        'idempotency_key' => $inIdempotencyKey,
                    ]);
                } catch (QueryException) {
                    $in = BankTransaction::query()
                        ->where('idempotency_key', $inIdempotencyKey)
                        ->firstOrFail();
                }
            }

            $postingIdempotencyKey = 'treasury-journal-trf-' . sha1(implode('|', [
                $transferGroupReference,
                $transactionDate,
                $description,
                (string) round($amount, 2),
            ]));

            if (! PostingSource::where('idempotency_key', $postingIdempotencyKey)->exists()) {
                $fromCashAccountCode = BankAccount::query()
                    ->where('id', $fromBankAccountId)
                    ->value('gl_account_code');
                $toCashAccountCode = BankAccount::query()
                    ->where('id', $toBankAccountId)
                    ->value('gl_account_code');

                if (! $fromCashAccountCode || ! $toCashAccountCode) {
                    throw new InvalidArgumentException('Transfer bank account GL codes must be set.');
                }

                $absAmount = round(abs($amount), 2);

                // Transfer journal: debit destination cash, credit origin cash.
                $this->journalService->post(
                    [
                        [
                            'account_code' => (string) $toCashAccountCode,
                            'description' => 'Treasury transfer in ' . $description,
                            'debit' => $absAmount,
                            'credit' => 0,
                        ],
                        [
                            'account_code' => (string) $fromCashAccountCode,
                            'description' => 'Treasury transfer out ' . $description,
                            'debit' => 0,
                            'credit' => $absAmount,
                        ],
                    ],
                    [
                        'journal_date' => $transactionDate,
                        'description' => 'Treasury transfer ' . $description,
                        'source_system' => 'treasury',
                        'source_type' => 'transfer',
                        'source_reference' => (string) $out->id,
                        'event_type' => 'treasury-transfer',
                        'idempotency_key' => $postingIdempotencyKey,
                        'payload' => [
                            'transfer_group_reference' => $transferGroupReference,
                            'from_bank_account_id' => $fromBankAccountId,
                            'to_bank_account_id' => $toBankAccountId,
                            'reference' => $reference,
                            'amount' => $amount,
                        ],
                    ]
                );
            }

            return ['out' => $out, 'in' => $in];
        });
    }

    /**
     * Return exactly two journal lines that balance (1 debit + 1 credit) for a single bank transaction.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function journalLinesForSingleTransaction(
        string $type,
        float $amount,
        float $absAmount,
        string $cashAccountCode,
        ?string $counterpartyGlAccountCode = null,
    ): array {
        $counterparty = $counterpartyGlAccountCode;

        // Defaults (Decision A): mapped per type, using existing COA codes.
        if (! $counterparty) {
            $counterparty = match ($type) {
                'deposit' => '121100', // Trade Receivables (credit side)
                'withdrawal' => '211100', // Trade Payables (debit side)
                'fee' => '820000', // Bank Charges (debit side)
                'adjustment' => null, // sign-dependent below
                default => null,
            };
        }

        return match ($type) {
            'deposit' => [
                [
                    'account_code' => $cashAccountCode,
                    'description' => 'Treasury deposit',
                    'debit' => $absAmount,
                    'credit' => 0,
                ],
                [
                    'account_code' => (string) $counterparty,
                    'description' => 'Treasury deposit counterparty',
                    'debit' => 0,
                    'credit' => $absAmount,
                ],
            ],
            'withdrawal' => [
                [
                    'account_code' => (string) $counterparty,
                    'description' => 'Treasury withdrawal counterparty',
                    'debit' => $absAmount,
                    'credit' => 0,
                ],
                [
                    'account_code' => $cashAccountCode,
                    'description' => 'Treasury withdrawal cash',
                    'debit' => 0,
                    'credit' => $absAmount,
                ],
            ],
            'fee' => [
                [
                    'account_code' => (string) $counterparty,
                    'description' => 'Treasury fee expense',
                    'debit' => $absAmount,
                    'credit' => 0,
                ],
                [
                    'account_code' => $cashAccountCode,
                    'description' => 'Treasury fee cash',
                    'debit' => 0,
                    'credit' => $absAmount,
                ],
            ],
            'adjustment' => (function () use ($amount, $absAmount, $cashAccountCode, $counterparty) {
                // Adjustment sign determines whether the other side is income vs expense.
                if ($amount > 0) {
                    $incomeAccount = $counterparty ?: '750000'; // Miscellaneous Income
                    return [
                        [
                            'account_code' => $cashAccountCode,
                            'description' => 'Treasury adjustment (cash increase)',
                            'debit' => $absAmount,
                            'credit' => 0,
                        ],
                        [
                            'account_code' => $incomeAccount,
                            'description' => 'Treasury adjustment income',
                            'debit' => 0,
                            'credit' => $absAmount,
                        ],
                    ];
                }

                $expenseAccount = $counterparty ?: '800000'; // Other Expenses
                return [
                    [
                        'account_code' => $expenseAccount,
                        'description' => 'Treasury adjustment (cash decrease)',
                        'debit' => $absAmount,
                        'credit' => 0,
                    ],
                    [
                        'account_code' => $cashAccountCode,
                        'description' => 'Treasury adjustment cash',
                        'debit' => 0,
                        'credit' => $absAmount,
                    ],
                ];
            })(),
            default => throw new InvalidArgumentException('Unknown treasury transaction type for journal posting.'),
        };
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
        DB::transaction(function () use ($statementLine, $transaction) {
            $lockedStatementLine = BankStatementLine::query()
                ->where('id', $statementLine->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTransaction = BankTransaction::query()
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStatementLine->bank_account_id !== $lockedTransaction->bank_account_id) {
                throw new \InvalidArgumentException('Statement line and transaction must belong to the same bank account.');
            }

            // Idempotency: if already matched to the same transaction, treat as success.
            if ($lockedStatementLine->isMatched()) {
                if ((int) $lockedStatementLine->bank_transaction_id === (int) $lockedTransaction->id) {
                    // Ensure transaction is reconciled; otherwise reconcile.
                    if (! $lockedTransaction->isReconciled()) {
                        $lockedTransaction->update(['reconciled_at' => now()]);
                    }
                    return;
                }

                throw new \InvalidArgumentException('Statement line is already matched to a different transaction.');
            }

            if ($lockedTransaction->isReconciled()) {
                throw new \InvalidArgumentException('Transaction is already reconciled.');
            }

            // Amount must match to avoid reconciliation against the wrong cash movement.
            if (round((float) $lockedStatementLine->amount, 2) !== round((float) $lockedTransaction->amount, 2)) {
                throw new \InvalidArgumentException('Statement line amount does not match transaction amount.');
            }

            $lockedStatementLine->update([
                'bank_transaction_id' => $lockedTransaction->id,
                'matched_at' => now(),
            ]);

            $lockedTransaction->update(['reconciled_at' => now()]);
        });
    }

    /**
     * Unmatch a statement line from its transaction.
     */
    public function unmatchStatementLine(BankStatementLine $statementLine): void
    {
        DB::transaction(function () use ($statementLine) {
            $lockedStatementLine = BankStatementLine::query()
                ->where('id', $statementLine->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedStatementLine->isMatched()) {
                return;
            }

            $transactionId = (int) $lockedStatementLine->bank_transaction_id;
            $lockedTransaction = BankTransaction::query()
                ->where('id', $transactionId)
                ->lockForUpdate()
                ->first();

            $lockedStatementLine->update([
                'bank_transaction_id' => null,
                'matched_at' => null,
            ]);

            if ($lockedTransaction) {
                $lockedTransaction->update(['reconciled_at' => null]);
            }
        });
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
