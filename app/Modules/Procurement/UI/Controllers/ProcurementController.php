<?php

namespace App\Modules\Procurement\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrderLine;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequest;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequestLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcurementController extends Controller
{
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
                'status' => 'draft',
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
            return $pr->fresh();
        });
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
        $request->update(['status' => PurchaseRequest::STATUS_SUBMITTED]);
        return redirect()->route('procurement.purchase-requests.show', $id)->with('success', __('P.R. submitted.'));
    }

    public function purchaseRequestApprove(int $id): RedirectResponse
    {
        $request = PurchaseRequest::findOrFail($id);
        if ($request->status !== PurchaseRequest::STATUS_SUBMITTED) {
            return redirect()->route('procurement.purchase-requests.show', $id)->with('error', __('Only submitted P.R. can be approved.'));
        }
        $request->update([
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approval_date' => now(),
        ]);
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
        $purchaseRequests = PurchaseRequest::where('status', 'draft')->orWhere('status', 'approved')->orderByDesc('id')->get();
        return view('procurement::purchase-orders.create', compact('vendors', 'purchaseRequests'));
    }

    public function purchaseOrderStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'purchase_request_id' => ['nullable', 'exists:purchase_requests,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.account_code' => ['nullable', 'string', 'max:32'],
        ]);
        $po = DB::transaction(function () use ($data) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'vendor_id' => $data['vendor_id'],
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => 'draft',
                'total' => 0,
                'currency' => $data['currency'] ?? $vendor->currency,
            ]);
            $total = 0;
            foreach ($data['lines'] as $line) {
                $qty = (float) $line['quantity'];
                $price = (float) $line['unit_price'];
                $amount = $qty * $price;
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'description' => $line['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'amount' => $amount,
                    'account_code' => $line['account_code'] ?? null,
                ]);
                $total += $amount;
            }
            $po->update(['total' => $total]);
            return $po->fresh();
        });
        return redirect()->route('procurement.purchase-orders.show', $po->id)->with('success', __('Purchase order created.'));
    }

    public function purchaseOrderShow(int $id): View
    {
        $order = PurchaseOrder::with(['vendor', 'lines', 'purchaseRequest'])->findOrFail($id);
        return view('procurement::purchase-orders.show', compact('order'));
    }

    public function purchaseOrderIssue(int $id): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('procurement.purchase-orders.show', $id)->with('error', __('Only draft P.O. can be issued.'));
        }
        $order->update(['status' => PurchaseOrder::STATUS_ISSUED]);
        return redirect()->route('procurement.purchase-orders.show', $id)->with('success', __('P.O. issued.'));
    }

    public function purchaseOrderReceive(int $id): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status !== PurchaseOrder::STATUS_ISSUED) {
            return redirect()->route('procurement.purchase-orders.show', $id)->with('error', __('Only issued P.O. can be marked received.'));
        }
        $order->update([
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'received_date' => now(),
        ]);
        return redirect()->route('procurement.purchase-orders.show', $id)->with('success', __('P.O. marked received.'));
    }

    protected function generatePrNumber(): string
    {
        $prefix = 'PR-' . date('Y') . '-';
        $last = PurchaseRequest::where('pr_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->pr_number, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    protected function generatePoNumber(): string
    {
        $prefix = 'PO-' . date('Y') . '-';
        $last = PurchaseOrder::where('po_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->po_number, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
