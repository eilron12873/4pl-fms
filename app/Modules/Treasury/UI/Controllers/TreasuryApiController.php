<?php

namespace App\Modules\Treasury\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;

class TreasuryApiController extends Controller
{
    public function __construct(
        protected TreasuryService $treasury,
    ) {
    }

    public function cashPosition(): JsonResponse
    {
        $position = $this->treasury->cashPosition();

        $accounts = $position['accounts']->map(fn (BankAccount $a) => [
            'id' => $a->id,
            'name' => $a->name,
            'currency' => $a->currency,
            'gl_account_code' => $a->gl_account_code,
            'balance' => $a->balance,
        ]);

        return response()->json([
            'success' => true,
            'accounts' => $accounts,
            'total_by_currency' => $position['total_by_currency'],
        ]);
    }

    public function bankAccountStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'gl_account_code' => ['nullable', 'string', 'max:20'],
            'opening_balance' => ['nullable', 'numeric'],
            'opened_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['gl_account_code'] = $data['gl_account_code'] ?? '1400';
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $account = $this->treasury->createAccount($data);

        return response()->json([
            'success' => true,
            'bank_account_id' => $account->id,
        ], 201);
    }

    public function transactionStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'type' => ['required', 'in:deposit,withdrawal,transfer,fee,adjustment'],
            'destination_bank_account_id' => ['required_if:type,transfer', 'exists:bank_accounts,id', 'different:bank_account_id'],
            'counterparty_gl_account_code' => ['nullable', 'string', 'max:20'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $bankAccountId = (int) $data['bank_account_id'];
        $amount = (float) $data['amount'];
        $type = (string) $data['type'];
        $reference = $data['reference'] ?? null;
        $counterpartyGlAccountCode = $data['counterparty_gl_account_code'] ?? null;

        try {
            if ($type === 'transfer') {
                if ($amount <= 0) {
                    throw new InvalidArgumentException('Transfer amount must be positive.');
                }

                $toBankAccountId = (int) $data['destination_bank_account_id'];
                $legs = $this->treasury->recordTransfer(
                    fromBankAccountId: $bankAccountId,
                    toBankAccountId: $toBankAccountId,
                    transactionDate: $data['transaction_date'],
                    description: $data['description'],
                    amount: $amount,
                    reference: $reference,
                );

                return response()->json([
                    'success' => true,
                    'transfer_group_reference' => $legs['out']->transfer_group_reference,
                    'out_transaction_id' => $legs['out']->id,
                    'in_transaction_id' => $legs['in']->id,
                ], 201);
            }

            $tx = $this->treasury->recordTransaction(
                bankAccountId: $bankAccountId,
                transactionDate: $data['transaction_date'],
                description: $data['description'],
                amount: $amount,
                type: $type,
                reference: $reference,
                sourceType: null,
                sourceId: null,
                counterpartyGlAccountCode: $counterpartyGlAccountCode,
            );

            return response()->json([
                'success' => true,
                'transaction_id' => $tx->id,
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PeriodLockedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reconciliationMatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'statement_line_id' => ['required', 'exists:bank_statement_lines,id'],
            'bank_transaction_id' => ['required', 'exists:bank_transactions,id'],
        ]);

        $statementLine = BankStatementLine::findOrFail($data['statement_line_id']);
        $transaction = BankTransaction::findOrFail($data['bank_transaction_id']);

        try {
            $this->treasury->matchStatementLineToTransaction($statementLine, $transaction);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function reconciliationUnmatch(int $statementLineId): JsonResponse
    {
        $statementLine = BankStatementLine::findOrFail($statementLineId);

        $this->treasury->unmatchStatementLine($statementLine);

        return response()->json(['success' => true]);
    }

    public function statementLineStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'statement_date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'bank_sequence' => ['nullable', 'string', 'max:100'],
        ]);

        $statementLine = $this->treasury->addStatementLine(
            (int) $data['bank_account_id'],
            $data['statement_date'],
            (float) $data['amount'],
            $data['description'] ?? null,
            $data['reference'] ?? null,
            $data['bank_sequence'] ?? null,
        );

        return response()->json([
            'success' => true,
            'statement_line_id' => $statementLine->id,
        ], 201);
    }
}

