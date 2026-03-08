<?php

namespace App\Modules\AccountsPayable\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsPayable\Application\ApReportingService;
use App\Modules\AccountsPayable\Application\BillService;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountsPayableController extends Controller
{
    public function __construct(
        protected BillService $billService,
        protected ApReportingService $reporting,
    ) {
    }

    public function index(): View
    {
        return view('accounts-payable::index');
    }

    public function vendors(Request $request): View
    {
        $query = Vendor::query();
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        $vendors = $query->orderBy('code')->paginate(20);
        return view('accounts-payable::vendors.index', compact('vendors'));
    }

    public function vendorCreate(): View
    {
        return view('accounts-payable::vendors.create');
    }

    public function vendorStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:vendors,code'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['payment_terms_days'] = $data['payment_terms_days'] ?? 30;
        Vendor::create($data);
        return redirect()->route('accounts-payable.vendors.index')->with('success', __('Vendor created.'));
    }

    public function bills(Request $request): View
    {
        $query = ApBill::with('vendor');
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        $bills = $query->orderByDesc('bill_date')->paginate(20);
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        return view('accounts-payable::bills.index', compact('bills', 'vendors'));
    }

    public function billShow(int $id): View
    {
        $bill = ApBill::with(['vendor', 'lines.journal', 'adjustments'])->findOrFail($id);
        return view('accounts-payable::bills.show', compact('bill'));
    }

    public function issueBill(int $id): RedirectResponse
    {
        $bill = ApBill::findOrFail($id);
        $this->billService->issueBill($bill);
        return redirect()->route('accounts-payable.bills.show', $id)->with('success', __('Bill issued.'));
    }

    public function statement(Request $request): View
    {
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        if (! $request->filled('vendor_id')) {
            return view('accounts-payable::statement', [
                'vendors' => $vendors,
                'vendor' => null,
                'balance' => 0,
                'bills' => collect(),
                'payments' => collect(),
            ]);
        }
        $vendorId = $request->integer('vendor_id');
        $fromDate = $request->filled('from_date') ? $request->string('from_date')->toString() : null;
        $toDate = $request->filled('to_date') ? $request->string('to_date')->toString() : null;
        $data = $this->reporting->statementOfAccount($vendorId, $fromDate, $toDate);
        return view('accounts-payable::statement', array_merge($data, ['vendors' => $vendors]));
    }

    public function aging(Request $request): View
    {
        $asOfDate = $request->filled('as_of_date') ? $request->string('as_of_date')->toString() : now()->toDateString();
        $rows = $this->reporting->agingReport($asOfDate);
        return view('accounts-payable::aging', compact('rows', 'asOfDate'));
    }

    public function payments(Request $request): View
    {
        $query = ApPayment::with('vendor');
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }
        $payments = $query->orderByDesc('payment_date')->paginate(20);
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        return view('accounts-payable::payments.index', compact('payments', 'vendors'));
    }

    public function paymentCreate(): View
    {
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        return view('accounts-payable::payments.create', compact('vendors'));
    }

    public function paymentStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.bill_id' => ['required', 'exists:ap_bills,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0'],
        ]);
        $data['allocations'] = $data['allocations'] ?? [];
        $this->billService->recordPayment($data);
        return redirect()->route('accounts-payable.payments.index')->with('success', __('Payment recorded.'));
    }

    public function creditNoteStore(Request $request, int $billId): RedirectResponse
    {
        $bill = ApBill::findOrFail($billId);
        $balanceDue = (float) $bill->total - (float) $bill->amount_allocated;
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $balanceDue],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->billService->createCreditNote($bill, (float) $data['amount'], $data['reason'] ?? '');
        return redirect()->route('accounts-payable.bills.show', $billId)->with('success', __('Credit note created.'));
    }
}
