<?php

namespace App\Modules\Treasury\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryController extends Controller
{
    public function __construct(
        protected TreasuryService $treasury,
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
        $this->treasury->createAccount($data);
        return redirect()->route('treasury.bank-accounts.index')->with('success', __('Bank account created.'));
    }

    public function bankAccountShow(int $id): View
    {
        $account = BankAccount::withSum('transactions', 'amount')->findOrFail($id);
        $transactions = $account->transactions()->orderByDesc('transaction_date')->paginate(20);
        return view('treasury::bank-accounts.show', compact('account', 'transactions'));
    }

    public function transactionCreate(int $accountId): View
    {
        $account = BankAccount::findOrFail($accountId);
        return view('treasury::transactions.create', compact('account'));
    }

    public function transactionStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'type' => ['required', 'in:deposit,withdrawal,transfer,fee,adjustment'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $this->treasury->recordTransaction(
            (int) $data['bank_account_id'],
            $data['transaction_date'],
            $data['description'],
            (float) $data['amount'],
            $data['type'],
            $data['reference'] ?? null,
        );
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
                ->get();
            $unreconciledTransactions = $account->transactions()
                ->whereNull('reconciled_at')
                ->orderBy('transaction_date')
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
        $this->treasury->matchStatementLineToTransaction($statementLine, $transaction);
        return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $statementLine->bank_account_id])->with('success', __('Matched.'));
    }

    public function unmatchReconciliation(int $statementLineId): RedirectResponse
    {
        $statementLine = BankStatementLine::findOrFail($statementLineId);
        $accountId = $statementLine->bank_account_id;
        $this->treasury->unmatchStatementLine($statementLine);
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
        return redirect()->route('treasury.reconciliation.index', ['bank_account_id' => $data['bank_account_id']])->with('success', __('Statement line added.'));
    }
}
