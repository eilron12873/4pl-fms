<?php

namespace App\Modules\AccountsReceivable\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsReceivable\Application\ArReportingService;
use App\Modules\AccountsReceivable\Application\InvoiceService;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArPayment;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ArReportingService $reporting,
    ) {
    }

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

    public function invoiceShow(int $id): View
    {
        $invoice = ArInvoice::with(['client', 'lines.journal', 'adjustments'])->findOrFail($id);
        return view('accounts-receivable::invoices.show', compact('invoice'));
    }

    public function issueInvoice(int $id): RedirectResponse
    {
        $invoice = ArInvoice::findOrFail($id);
        $this->invoiceService->issueInvoice($invoice);
        return redirect()->route('accounts-receivable.invoices.show', $id)->with('success', __('Invoice issued.'));
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
        $data = $request->validate([
            'client_id' => ['required', 'exists:billing_clients,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
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
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $balanceDue],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->invoiceService->createCreditNote($invoice, (float) $data['amount'], $data['reason'] ?? '');
        return redirect()->route('accounts-receivable.invoices.show', $invoiceId)->with('success', __('Credit note created.'));
    }
}
