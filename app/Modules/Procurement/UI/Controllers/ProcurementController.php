<?php

namespace App\Modules\Procurement\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillLine;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrderLine;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequest;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequestLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Intl\Currencies;

class ProcurementController extends Controller
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function index(): View
    {
        return view('procurement::index');
    }

    public function purchaseRequests(Request $request): View
    {
        $query = PurchaseRequest::withCount('lines')->orderByDesc('request_date');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        $requests = $query->paginate(20)->withQueryString();

        return view('procurement::purchase-requests.index', compact('requests'));
    }

    public function purchaseRequestCreate(): View
    {
        return view('procurement::purchase-requests.create');
    }

    public function purchaseRequestStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'requested_by' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:128'],
            'request_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.account_code' => ['nullable', 'string', 'max:32'],
        ]);

        $pr = DB::transaction(function () use ($data) {
            $pr = PurchaseRequest::create([
                'pr_number' => $this->generatePrNumber(),
                'requested_by' => $data['requested_by'] ?? null,
                'department' => $data['department'] ?? null,
                'request_date' => $data['request_date'],
                'status' => PurchaseRequest::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['lines'] as $line) {
                $qty = (float) $line['quantity'];
                $cost = (float) $line['estimated_unit_cost'];
                PurchaseRequestLine::create([
                    'purchase_request_id' => $pr->id,
                    'description' => $line['description'],
                    'quantity' => $qty,
                    'estimated_unit_cost' => $cost,
                    'account_code' => $line['account_code'] ?? null,
                ]);
            }

            return $pr->fresh(['lines']);
        });

        $this->audit->log(
            description: __('Purchase request :num created', ['num' => $pr->pr_number]),
            event: 'procurement.pr.created',
            subject: $pr,
            properties: [
                'group' => 'procurement',
                'after' => [
                    'pr_number' => $pr->pr_number,
                    'status' => $pr->status,
                ],
            ],
            logName: AuditService::LOG_PROCUREMENT,
        );

        return redirect()->route('procurement.purchase-requests.show', $pr->id)->with('success', __('Purchase request created.'));
    }

    public function purchaseRequestShow(int $id): View
    {
        $request = PurchaseRequest::with('lines')->findOrFail($id);

        return view('procurement::purchase-requests.show', compact('request'));
    }

    public function purchaseRequestSubmit(int $id): RedirectResponse
    {
        $request = PurchaseRequest::findOrFail($id);
        if ($request->status !== PurchaseRequest::STATUS_DRAFT) {
            return redirect()->route('procurement.purchase-requests.show', $id)->with('error', __('Only draft P.R. can be submitted.'));
        }
        $before = ['status' => $request->status];
        $request->update(['status' => PurchaseRequest::STATUS_SUBMITTED]);

        $this->audit->log(
            description: __('Purchase request :num submitted', ['num' => $request->pr_number]),
            event: 'procurement.pr.submitted',
            subject: $request,
            properties: [
                'group' => 'procurement',
                'before' => $before,
                'after' => ['status' => $request->status],
            ],
            logName: AuditService::LOG_PROCUREMENT,
        );

        return redirect()->route('procurement.purchase-requests.show', $id)->with('success', __('P.R. submitted.'));
    }

    public function purchaseRequestApprove(int $id): RedirectResponse
    {
        $request = PurchaseRequest::findOrFail($id);
        if ($request->status !== PurchaseRequest::STATUS_SUBMITTED) {
            return redirect()->route('procurement.purchase-requests.show', $id)->with('error', __('Only submitted P.R. can be approved.'));
        }
        $before = ['status' => $request->status, 'approval_date' => $request->approval_date?->toDateString()];
        $request->update([
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approval_date' => now(),
        ]);
        $request->refresh();

        $this->audit->log(
            description: __('Purchase request :num approved', ['num' => $request->pr_number]),
            event: 'procurement.pr.approved',
            subject: $request,
            properties: [
                'group' => 'procurement',
                'before' => $before,
                'after' => [
                    'status' => $request->status,
                    'approval_date' => $request->approval_date?->toDateString(),
                ],
            ],
            logName: AuditService::LOG_PROCUREMENT,
        );

        return redirect()->route('procurement.purchase-requests.show', $id)->with('success', __('P.R. approved.'));
    }

    public function purchaseOrders(Request $request): View
    {
        $query = PurchaseOrder::with('vendor')->withCount('lines')->orderByDesc('order_date');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }
        $orders = $query->paginate(20)->withQueryString();
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();

        return view('procurement::purchase-orders.index', compact('orders', 'vendors'));
    }

    public function purchaseOrderCreate(Request $request): View
    {
        $vendors = Vendor::where('is_active', true)->orderBy('code')->get();
        $purchaseRequests = PurchaseRequest::query()
            ->where('status', PurchaseRequest::STATUS_APPROVED)
            ->orderByDesc('id')
            ->get();

        $importLines = null;
        $prefillPrId = $request->filled('prefill_pr') ? $request->integer('prefill_pr') : 0;
        if ($prefillPrId > 0) {
            $prefillPr = PurchaseRequest::with('lines')
                ->where('id', $prefillPrId)
                ->where('status', PurchaseRequest::STATUS_APPROVED)
                ->first();
            if ($prefillPr) {
                $importLines = $prefillPr->lines->map(fn (PurchaseRequestLine $l) => [
                    'description' => $l->description,
                    'quantity' => (string) $l->quantity,
                    'unit_price' => (string) $l->estimated_unit_cost,
                    'account_code' => $l->account_code ?? '',
                    'purchase_request_line_id' => $l->id,
                ])->all();
            }
        }

        return view('procurement::purchase-orders.create', compact('vendors', 'purchaseRequests', 'importLines', 'prefillPrId'));
    }

    public function purchaseOrderStore(Request $request): RedirectResponse
    {
        $currencyCodes = array_keys(Currencies::getNames());
        $request->merge([
            'currency' => $request->filled('currency') ? strtoupper(trim((string) $request->input('currency'))) : null,
        ]);

        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'purchase_request_id' => ['nullable', 'integer', 'exists:purchase_requests,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in($currencyCodes)],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.account_code' => ['nullable', 'string', 'max:32'],
            'lines.*.purchase_request_line_id' => ['nullable', 'integer', 'exists:purchase_request_lines,id'],
        ]);

        if (! empty($data['purchase_request_id'])) {
            $linkedPr = PurchaseRequest::query()->find($data['purchase_request_id']);
            if ($linkedPr === null || $linkedPr->status !== PurchaseRequest::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'purchase_request_id' => [__('The selected purchase request must be approved.')],
                ]);
            }
        }

        foreach ($data['lines'] as $i => $line) {
            $prLineId = $line['purchase_request_line_id'] ?? null;
            if ($prLineId === null || $prLineId === '') {
                continue;
            }
            $prLine = PurchaseRequestLine::query()->find((int) $prLineId);
            if ($prLine === null) {
                throw ValidationException::withMessages([
                    "lines.{$i}.purchase_request_line_id" => [__('Invalid P.R. line reference.')],
                ]);
            }
            if (empty($data['purchase_request_id']) || (int) $prLine->purchase_request_id !== (int) $data['purchase_request_id']) {
                throw ValidationException::withMessages([
                    "lines.{$i}.purchase_request_line_id" => [__('P.R. line must belong to the selected purchase request.')],
                ]);
            }
        }

        $po = DB::transaction(function () use ($data) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'vendor_id' => $data['vendor_id'],
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'total' => 0,
                'currency' => $data['currency'] ?? $vendor->currency,
            ]);
            $total = 0;
            foreach ($data['lines'] as $line) {
                $qty = (float) $line['quantity'];
                $price = (float) $line['unit_price'];
                $amount = $qty * $price;
                $prLineId = isset($line['purchase_request_line_id']) && $line['purchase_request_line_id'] !== ''
                    ? (int) $line['purchase_request_line_id']
                    : null;
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'purchase_request_line_id' => $prLineId,
                    'description' => $line['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'amount' => $amount,
                    'account_code' => $line['account_code'] ?? null,
                ]);
                $total += $amount;
            }
            $po->update(['total' => $total]);

            return $po->fresh(['vendor', 'lines']);
        });

        $this->audit->log(
            description: __('Purchase order :num created', ['num' => $po->po_number]),
            event: 'procurement.po.created',
            subject: $po,
            properties: [
                'group' => 'procurement',
                'after' => [
                    'po_number' => $po->po_number,
                    'status' => $po->status,
                    'total' => (string) $po->total,
                    'purchase_request_id' => $po->purchase_request_id,
                ],
            ],
            logName: AuditService::LOG_PROCUREMENT,
        );

        return redirect()->route('procurement.purchase-orders.show', $po->id)->with('success', __('Purchase order created.'));
    }

    public function purchaseOrderShow(int $id): View
    {
        $order = PurchaseOrder::with(['vendor', 'lines', 'purchaseRequest'])->findOrFail($id);

        $billedTotal = ApBillLine::whereHas('bill', function ($q) use ($order): void {
            $q->where('purchase_order_id', $order->id);
        })->sum('amount');

        $poVariance = [
            'po_total' => (float) ($order->total ?? 0),
            'billed_total' => (float) $billedTotal,
            'remaining' => (float) ($order->total ?? 0) - (float) $billedTotal,
        ];

        return view('procurement::purchase-orders.show', compact('order', 'poVariance'));
    }

    public function purchaseOrderIssue(int $id): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('procurement.purchase-orders.show', $id)->with('error', __('Only draft P.O. can be issued.'));
        }
        $before = ['status' => $order->status];
        $order->update(['status' => PurchaseOrder::STATUS_ISSUED]);

        $this->audit->log(
            description: __('Purchase order :num issued', ['num' => $order->po_number]),
            event: 'procurement.po.issued',
            subject: $order,
            properties: [
                'group' => 'procurement',
                'before' => $before,
                'after' => ['status' => $order->status],
            ],
            logName: AuditService::LOG_PROCUREMENT,
        );

        return redirect()->route('procurement.purchase-orders.show', $id)->with('success', __('P.O. issued.'));
    }

    public function purchaseOrderReceive(int $id): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status !== PurchaseOrder::STATUS_ISSUED) {
            return redirect()->route('procurement.purchase-orders.show', $id)->with('error', __('Only issued P.O. can be marked received.'));
        }
        $before = ['status' => $order->status, 'received_date' => $order->received_date?->toDateString()];
        $order->update([
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'received_date' => now(),
        ]);
        $order->refresh();

        $this->audit->log(
            description: __('Purchase order :num received', ['num' => $order->po_number]),
            event: 'procurement.po.received',
            subject: $order,
            properties: [
                'group' => 'procurement',
                'before' => $before,
                'after' => [
                    'status' => $order->status,
                    'received_date' => $order->received_date?->toDateString(),
                ],
            ],
            logName: AuditService::LOG_PROCUREMENT,
        );

        return redirect()->route('procurement.purchase-orders.show', $id)->with('success', __('P.O. marked received.'));
    }

    protected function generatePrNumber(): string
    {
        $prefix = 'PR-'.date('Y').'-';
        $last = PurchaseRequest::where('pr_number', 'like', $prefix.'%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->pr_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    protected function generatePoNumber(): string
    {
        $prefix = 'PO-'.date('Y').'-';
        $last = PurchaseOrder::where('po_number', 'like', $prefix.'%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->po_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
