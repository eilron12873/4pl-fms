<?php

namespace App\Modules\AccountsPayable\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsPayable\Application\ApReportingService;
use App\Modules\AccountsPayable\Application\AmountToWords;
use App\Modules\AccountsPayable\Application\BillService;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApCheck;
use App\Modules\AccountsPayable\Infrastructure\Models\ApPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\ApVoucher;
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

    public function billCreate(Request $request): View
    {
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        $purchaseOrder = null;
        $presetLines = [];
        if ($request->filled('purchase_order_id')) {
            $po = \App\Modules\Procurement\Infrastructure\Models\PurchaseOrder::with(['vendor', 'lines'])->find($request->integer('purchase_order_id'));
            if ($po) {
                $purchaseOrder = $po;
                $presetLines = $po->lines->map(fn ($l) => ['description' => $l->description, 'amount' => (string) $l->amount])->values()->all();
            }
        }
        return view('accounts-payable::bills.create', compact('vendors', 'purchaseOrder', 'presetLines'));
    }

    public function billStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:bill_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
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
        if (! empty($data['purchase_order_id'])) {
            $data['purchase_order_id'] = (int) $data['purchase_order_id'];
        }
        $bill = $this->billService->createManualBill($data);
        return redirect()->route('accounts-payable.bills.show', $bill->id)->with('success', __('Bill created. You can issue it when ready.'));
    }

    public function billEdit(int $id): View|RedirectResponse
    {
        $bill = ApBill::with('lines')->findOrFail($id);
        if ($bill->isIssued()) {
            return redirect()->route('accounts-payable.bills.show', $id)->with('error', __('Cannot edit an issued bill.'));
        }
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        $lines = $bill->lines->map(fn ($l) => ['description' => $l->description, 'amount' => $l->amount])->values()->all();
        return view('accounts-payable::bills.edit', compact('bill', 'vendors', 'lines'));
    }

    public function billUpdate(Request $request, int $id): RedirectResponse
    {
        $bill = ApBill::findOrFail($id);
        if ($bill->isIssued()) {
            return redirect()->route('accounts-payable.bills.show', $id)->with('error', __('Cannot edit an issued bill.'));
        }
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:bill_date'],
            'currency' => ['nullable', 'string', 'size:3'],
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
        $this->billService->updateDraftBill($bill, $data);
        return redirect()->route('accounts-payable.bills.show', $id)->with('success', __('Bill updated.'));
    }

    public function billShow(int $id): View
    {
        $bill = ApBill::with(['vendor', 'lines.journal', 'adjustments', 'purchaseOrder'])->findOrFail($id);
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
        $bankAccounts = \App\Modules\Treasury\Infrastructure\Models\BankAccount::where('is_active', true)->orderBy('name')->get();
        return view('accounts-payable::payments.create', compact('vendors', 'bankAccounts'));
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
            'payment_method' => ['nullable', 'string', 'in:ach,check'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.bill_id' => ['required', 'exists:ap_bills,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0'],
        ]);
        $data['allocations'] = $data['allocations'] ?? [];
        $data['payment_method'] = $data['payment_method'] ?? 'ach';
        if ($data['payment_method'] === 'check' && empty($data['bank_account_id'])) {
            $data['bank_account_id'] = null;
        }
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

    public function vouchers(Request $request): View
    {
        $query = ApVoucher::with('payment.vendor')->orderByDesc('voucher_date')->orderByDesc('id');
        if ($request->filled('from_date')) {
            $query->whereDate('voucher_date', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('voucher_date', '<=', $request->string('to_date'));
        }
        $vouchers = $query->paginate(20)->withQueryString();
        return view('accounts-payable::vouchers.index', compact('vouchers'));
    }

    public function voucherShow(int $id): View
    {
        $voucher = ApVoucher::with(['payment.vendor', 'payment.billPayments.bill'])->findOrFail($id);
        return view('accounts-payable::vouchers.show', compact('voucher'));
    }

    public function checks(Request $request): View
    {
        $query = ApCheck::with(['payment.vendor', 'bankAccount'])->orderByDesc('check_date')->orderByDesc('id');
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->integer('bank_account_id'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('check_date', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('check_date', '<=', $request->string('to_date'));
        }
        $checks = $query->paginate(20)->withQueryString();
        $bankAccounts = \App\Modules\Treasury\Infrastructure\Models\BankAccount::where('is_active', true)->orderBy('name')->get();
        return view('accounts-payable::checks.index', compact('checks', 'bankAccounts'));
    }

    public function checkShow(int $id): View
    {
        $check = ApCheck::with(['payment.vendor', 'payment.billPayments.bill', 'bankAccount'])->findOrFail($id);
        $amountInWords = AmountToWords::forCheck((float) ($check->amount ?? 0));
        return view('accounts-payable::checks.show', compact('check', 'amountInWords'));
    }

    public function voidCheck(int $id): RedirectResponse
    {
        $check = ApCheck::findOrFail($id);
        if ($check->status === ApCheck::STATUS_VOID) {
            return redirect()->route('accounts-payable.checks.show', $id)->with('error', __('Check is already void.'));
        }
        $check->update(['status' => ApCheck::STATUS_VOID]);
        return redirect()->route('accounts-payable.checks.show', $id)->with('success', __('Check voided.'));
    }
}
