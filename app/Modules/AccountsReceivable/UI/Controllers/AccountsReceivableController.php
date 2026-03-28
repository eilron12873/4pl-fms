<?php

namespace App\Modules\AccountsReceivable\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsReceivable\Application\ArReportingService;
use App\Modules\AccountsReceivable\Application\InvoiceService;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceAdjustment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArPayment;
use App\Modules\ApprovalWorkflows\Application\ApprovalWorkflowService;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ArReportingService $reporting,
        protected ApprovalWorkflowService $approvalWorkflows,
    ) {}

    public function index(): View
    {
        return view('accounts-receivable::index');
    }

    public function invoices(Request $request): View
    {
        $query = ArInvoice::with('client');
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        $invoices = $query->orderByDesc('invoice_date')->paginate(20);
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();

        return view('accounts-receivable::invoices.index', compact('invoices', 'clients'));
    }

    public function invoiceCreate(): View
    {
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();

        return view('accounts-receivable::invoices.create', compact('clients'));
    }

    public function invoiceStore(Request $request): RedirectResponse
    {
        $currencyCodes = BillingClient::intlCurrencyCodes();
        $request->merge([
            'currency' => $request->filled('currency') ? strtoupper(trim((string) $request->input('currency'))) : null,
        ]);
        $data = $request->validate([
            'client_id' => ['required', 'exists:billing_clients,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in($currencyCodes)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['lines'] = array_values(array_filter($data['lines'], function ($l) {
            return isset($l['description']) && trim((string) $l['description']) !== '' && isset($l['amount']) && (float) $l['amount'] > 0;
        }));
        if (empty($data['lines'])) {
            return redirect()->back()->withInput($request->input())->withErrors(['lines' => __('At least one line with description and amount is required.')]);
        }
        $invoice = $this->invoiceService->createManualInvoice($data);

        return redirect()->route('accounts-receivable.invoices.show', $invoice->id)->with('success', __('Invoice created. You can issue it when ready.'));
    }

    public function invoiceEdit(int $id): View|RedirectResponse
    {
        $invoice = ArInvoice::with('lines')->findOrFail($id);
        if (! $invoice->isDraft()) {
            return redirect()->route('accounts-receivable.invoices.show', $id)->with('error', __('Can only edit draft invoices.'));
        }
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();

        return view('accounts-receivable::invoices.edit', compact('invoice', 'clients'));
    }

    public function invoiceUpdate(Request $request, int $id): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($id);
        if (! $invoice->isDraft()) {
            return redirect()->route('accounts-receivable.invoices.show', $id)->with('error', __('Can only edit draft invoices.'));
        }
        $currencyCodes = BillingClient::intlCurrencyCodes();
        $request->merge([
            'currency' => $request->filled('currency') ? strtoupper(trim((string) $request->input('currency'))) : null,
        ]);
        $data = $request->validate([
            'client_id' => ['required', 'exists:billing_clients,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in($currencyCodes)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['lines'] = array_values(array_filter($data['lines'], function ($l) {
            return isset($l['description']) && trim((string) $l['description']) !== '' && isset($l['amount']) && (float) $l['amount'] > 0;
        }));
        if (empty($data['lines'])) {
            return redirect()->back()->withInput($request->input())->withErrors(['lines' => __('At least one line with description and amount is required.')]);
        }
        $this->invoiceService->updateDraftInvoice($invoice, $data);

        return redirect()->route('accounts-receivable.invoices.show', $id)->with('success', __('Invoice updated.'));
    }

    public function invoiceShow(int $id): View
    {
        $invoice = ArInvoice::with(['client', 'lines.journal', 'adjustments'])->findOrFail($id);
        $pendingCreditSum = ArInvoiceAdjustment::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', 'credit_note')
            ->where('status', 'pending_approval')
            ->sum('amount'); // negative amounts

        $pendingCreditAbs = (float) (-$pendingCreditSum);
        $availableCreditNoteMax = max((float) $invoice->balance_due - $pendingCreditAbs, 0.0);

        return view('accounts-receivable::invoices.show', compact('invoice', 'availableCreditNoteMax'));
    }

    public function issueInvoice(int $id): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($id);
        try {
            $this->invoiceService->issueInvoice($invoice);

            return redirect()->route('accounts-receivable.invoices.show', $id)->with('success', __('Invoice issued.'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('accounts-receivable.invoices.show', $id)->with('error', __($e->getMessage()));
        }
    }

    public function invoiceSubmit(int $id): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($id);
        if (! $invoice->isDraft()) {
            return redirect()->route('accounts-receivable.invoices.show', $id)->with('error', __('Only draft invoices can be submitted for approval.'));
        }

        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $this->approvalWorkflows->requestApproval(
            approvable: $invoice,
            approvalType: 'ar_invoice',
            requestedBy: $userId,
            comments: null,
            metadata: ['source' => 'ui.invoiceSubmit']
        );
        $invoice->update(['status' => 'pending_approval']);

        return redirect()->route('accounts-receivable.invoices.show', $id)->with('success', __('Invoice submitted for approval.'));
    }

    public function invoiceApprove(int $id): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($id);
        if (! $invoice->isPendingApproval()) {
            return redirect()->route('accounts-receivable.invoices.show', $id)->with('error', __('Only pending invoices can be approved.'));
        }

        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $invoice,
            approvalType: 'ar_invoice',
            requestedBy: $userId,
            comments: null,
            metadata: ['source' => 'ui.invoiceApprove']
        );
        $this->approvalWorkflows->approve($approval, $userId, null);

        $invoice->update(['status' => 'approved']);

        return redirect()->route('accounts-receivable.invoices.show', $id)->with('success', __('Invoice approved.'));
    }

    public function invoiceReject(int $id, Request $request): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($id);
        if (! $invoice->isPendingApproval()) {
            return redirect()->route('accounts-receivable.invoices.show', $id)->with('error', __('Only pending invoices can be rejected.'));
        }

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $invoice,
            approvalType: 'ar_invoice',
            requestedBy: $userId,
            comments: null,
            metadata: ['source' => 'ui.invoiceReject']
        );
        $this->approvalWorkflows->reject($approval, $userId, $data['comments'] ?? null);

        $invoice->update(['status' => 'draft']);

        return redirect()->route('accounts-receivable.invoices.show', $id)->with('success', __('Invoice rejected back to draft.'));
    }

    public function statement(Request $request): View
    {
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();
        if (! $request->filled('client_id')) {
            return view('accounts-receivable::statement', [
                'clients' => $clients,
                'client' => null,
                'balance' => 0,
                'invoices' => collect(),
                'payments' => collect(),
            ]);
        }
        $clientId = $request->integer('client_id');
        $fromDate = $request->filled('from_date') ? $request->string('from_date')->toString() : null;
        $toDate = $request->filled('to_date') ? $request->string('to_date')->toString() : null;
        $data = $this->reporting->statementOfAccount($clientId, $fromDate, $toDate);

        return view('accounts-receivable::statement', array_merge($data, ['clients' => $clients]));
    }

    public function aging(Request $request): View
    {
        $asOfDate = $request->filled('as_of_date') ? $request->string('as_of_date')->toString() : now()->toDateString();
        $rows = $this->reporting->agingReport($asOfDate);

        return view('accounts-receivable::aging', compact('rows', 'asOfDate'));
    }

    public function payments(Request $request): View
    {
        $query = ArPayment::with('client');
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }
        $payments = $query->orderByDesc('payment_date')->paginate(20);
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();

        return view('accounts-receivable::payments.index', compact('payments', 'clients'));
    }

    public function paymentCreate(): View
    {
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();

        return view('accounts-receivable::payments.create', compact('clients'));
    }

    public function paymentStore(Request $request): RedirectResponse
    {
        $currencyCodes = BillingClient::intlCurrencyCodes();
        $request->merge([
            'currency' => strtoupper(trim((string) $request->input('currency', ''))),
        ]);
        $data = $request->validate([
            'client_id' => ['required', 'exists:billing_clients,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencyCodes)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'exists:ar_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0'],
        ]);
        $data['allocations'] = $data['allocations'] ?? [];
        $this->invoiceService->recordPayment($data);

        return redirect()->route('accounts-receivable.payments.index')->with('success', __('Payment recorded.'));
    }

    public function creditNoteStore(Request $request, int $invoiceId): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($invoiceId);
        $balanceDue = (float) $invoice->total - (float) $invoice->amount_allocated;

        $pendingCreditSum = ArInvoiceAdjustment::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', 'credit_note')
            ->where('status', 'pending_approval')
            ->sum('amount'); // negative amounts
        $pendingCreditAbs = (float) (-$pendingCreditSum);
        $availableMax = max($balanceDue - $pendingCreditAbs, 0.0);

        if ($availableMax < 0.01) {
            return redirect()->route('accounts-receivable.invoices.show', $invoiceId)->with('error', __('Cannot request a credit note: available credit note capacity is zero.'));
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$availableMax],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $adjustment = $this->invoiceService->requestCreditNote(
            $invoice,
            (float) $data['amount'],
            (string) ($data['reason'] ?? ''),
        );

        $this->approvalWorkflows->requestApproval(
            approvable: $adjustment,
            approvalType: 'credit_note',
            requestedBy: $userId,
            comments: $data['reason'] ?? null,
            metadata: ['source' => 'ui.creditNoteStore', 'invoice_id' => $invoice->id, 'adjustment_id' => $adjustment->id],
        );

        return redirect()->route('accounts-receivable.invoices.show', $invoiceId)->with('success', __('Credit note requested for approval.'));
    }
}
