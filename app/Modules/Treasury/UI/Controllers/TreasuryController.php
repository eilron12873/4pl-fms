<?php

namespace App\Modules\Treasury\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Services\AuditService;
use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryController extends Controller
{
    public function __construct(
        protected TreasuryService $treasury,
        protected AuditService $audit,
    ) {
    }

    public function index(): View
    {
        $position = $this->treasury->cashPosition();
        return view('treasury::index', $position);
    }

    public function bankAccounts(): View
    {
        $accounts = BankAccount::withSum('transactions', 'amount')
            ->orderBy('name')
            ->paginate(20);
        return view('treasury::bank-accounts.index', compact('accounts'));
    }

    public function bankAccountCreate(): View
    {
        return view('treasury::bank-accounts.create');
    }

    public function bankAccountStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'gl_account_code' => ['nullable', 'string', 'max:20'],
            'opening_balance' => ['nullable', 'numeric'],
            'opened_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['gl_account_code'] = $data['gl_account_code'] ?? '1400';
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $account = $this->treasury->createAccount($data);
        $this->audit->log(
            description: 'Bank account created',
            event: 'treasury.bank_account.created',
            subject: $account,
            properties: [
                'bank_account_id' => $account->id,
                'currency' => $account->currency,
                'gl_account_code' => $account->gl_account_code,
            ],
        );
        return redirect()->route('treasury.bank-accounts.index')->with('success', __('Bank account created.'));
    }

    public function bankAccountShow(int $id): View
    {
        $account = BankAccount::withSum('transactions', 'amount')->findOrFail($id);
        $transactions = $account->transactions()
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20);
        return view('treasury::bank-accounts.show', compact('account', 'transactions'));
    }

    public function transactionCreate(int $accountId): View
    {
        $account = BankAccount::findOrFail($accountId);
        $destinationAccounts = BankAccount::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('treasury::transactions.create', compact('account', 'destinationAccounts'));
    }

    public function transactionStore(Request $request): RedirectResponse
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
                $toBankAccountId = (int) $data['destination_bank_account_id'];
                if ($amount <= 0) {
                    return redirect()->back()->withInput()->withErrors([
                        'amount' => __('Transfer amount must be positive.'),
                    ]);
                }

                $legs = $this->treasury->recordTransfer(
                    fromBankAccountId: $bankAccountId,
                    toBankAccountId: $toBankAccountId,
                    transactionDate: $data['transaction_date'],
                    description: $data['description'],
                    amount: $amount,
                    reference: $reference,
                );
                $this->audit->logFinancial(
                    description: 'Treasury transfer recorded',
                    subject: null,
                    properties: [
                        'from_bank_account_id' => $bankAccountId,
                        'to_bank_account_id' => $toBankAccountId,
                        'transfer_group_reference' => $legs['out']->transfer_group_reference ?? null,
                        'out_transaction_id' => $legs['out']->id,
                        'in_transaction_id' => $legs['in']->id,
                        'amount' => $amount,
                        'transaction_date' => $data['transaction_date'],
                        'reference' => $reference,
                    ],
                    event: 'treasury.transfer.recorded',
                );
            } else {
                // Single-leg transactions: validate sign convention.
                if ($type === 'deposit' && $amount <= 0) {
                    return redirect()->back()->withInput()->withErrors(['amount' => __('Deposit amount must be positive.')]);
                }
                if ($type === 'withdrawal' && $amount >= 0) {
                    return redirect()->back()->withInput()->withErrors(['amount' => __('Withdrawal amount must be negative.')]);
                }
                if ($type === 'fee' && $amount >= 0) {
                    return redirect()->back()->withInput()->withErrors(['amount' => __('Fee amount must be negative.')]);
                }
                if ($type === 'adjustment' && abs($amount) < 0.00001) {
                    return redirect()->back()->withInput()->withErrors(['amount' => __('Adjustment amount cannot be zero.')]);
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
                $this->audit->logFinancial(
                    description: 'Treasury transaction recorded',
                    subject: $tx,
                    properties: [
                        'transaction_id' => $tx->id,
                        'bank_account_id' => $tx->bank_account_id,
                        'type' => $tx->type,
                        'amount' => (float) $tx->amount,
                        'reference' => $tx->reference,
                    ],
                    event: 'treasury.transaction.recorded',
                );
            }
        } catch (\Throwable $e) {
            if ($e instanceof PeriodLockedException) {
                return redirect()->back()->withInput()->withErrors([
                    'period' => __($e->getMessage()),
                ]);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('treasury.bank-accounts.show', $data['bank_account_id'])->with('success', __('Transaction recorded.'));
    }

    public function reconciliation(Request $request): View
    {
        $accountId = $request->integer('bank_account_id');
        $account = $accountId ? BankAccount::withSum('transactions', 'amount')->find($accountId) : null;
        $accounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        $unmatchedStatementLines = collect();
        $unreconciledTransactions = collect();
        if ($account) {
            $unmatchedStatementLines = $account->statementLines()
                ->whereNull('bank_transaction_id')
                ->orderBy('statement_date')
                ->orderBy('id')
                ->limit(200)
                ->get();
            $unreconciledTransactions = $account->transactions()
                ->whereNull('reconciled_at')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->limit(200)
                ->get();
        }

        return view('treasury::reconciliation.index', compact('account', 'accounts', 'unmatchedStatementLines', 'unreconciledTransactions'));
    }

    public function matchReconciliation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'statement_line_id' => ['required', 'exists:bank_statement_lines,id'],
            'bank_transaction_id' => ['required', 'exists:bank_transactions,id'],
        ]);
        $statementLine = BankStatementLine::findOrFail($data['statement_line_id']);
        $transaction = BankTransaction::findOrFail($data['bank_transaction_id']);
        try {
            $this->treasury->matchStatementLineToTransaction($statementLine, $transaction);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $statementLine->bank_account_id])
                ->with('error', $e->getMessage());
        }
        $this->audit->log(
            description: 'Statement line matched to transaction',
            event: 'treasury.reconciliation.matched',
            subject: $statementLine->fresh(),
            properties: [
                'statement_line_id' => $statementLine->id,
                'bank_transaction_id' => $transaction->id,
                'bank_account_id' => $statementLine->bank_account_id,
            ],
        );
        return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $statementLine->bank_account_id])->with('success', __('Matched.'));
    }

    public function unmatchReconciliation(int $statementLineId): RedirectResponse
    {
        $statementLine = BankStatementLine::findOrFail($statementLineId);
        $accountId = $statementLine->bank_account_id;
        try {
            $this->treasury->unmatchStatementLine($statementLine);
        } catch (\Throwable $e) {
            return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $accountId])
                ->with('error', $e->getMessage());
        }
        $this->audit->log(
            description: 'Statement line unmatched',
            event: 'treasury.reconciliation.unmatched',
            subject: $statementLine->fresh(),
            properties: [
                'statement_line_id' => $statementLine->id,
                'bank_account_id' => $accountId,
            ],
        );
        return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $accountId])->with('success', __('Unmatched.'));
    }

    public function statementLineStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'statement_date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'bank_sequence' => ['nullable', 'string', 'max:100'],
        ]);
        $this->treasury->addStatementLine(
            (int) $data['bank_account_id'],
            $data['statement_date'],
            (float) $data['amount'],
            $data['description'] ?? null,
            $data['reference'] ?? null,
            $data['bank_sequence'] ?? null,
        );
        $this->audit->log(
            description: 'Statement line added',
            event: 'treasury.statement_line.added',
            subject: null,
            properties: [
                'bank_account_id' => $data['bank_account_id'],
                'statement_date' => $data['statement_date'],
                'amount' => (float) $data['amount'],
                'reference' => $data['reference'] ?? null,
                'bank_sequence' => $data['bank_sequence'] ?? null,
            ],
        );
        return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $data['bank_account_id']])->with('success', __('Statement line added.'));
    }
}
