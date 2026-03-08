<?php

namespace App\Modules\InventoryValuation\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InventoryValuation\Application\InventoryValuationService;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryItem;
use App\Modules\InventoryValuation\Infrastructure\Models\InventoryMovement;
use App\Modules\InventoryValuation\Infrastructure\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryValuationController extends Controller
{
    public function __construct(
        protected InventoryValuationService $valuation,
    ) {
    }

    public function index(): View
    {
        $totalValue = $this->valuation->totalValuation(null);
        $report = $this->valuation->valuationReport(null, null);
        return view('inventory-valuation::index', compact('totalValue', 'report'));
    }

    public function valuation(Request $request): View
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $itemId = $request->integer('item_id') ?: null;
        $report = $this->valuation->valuationReport($warehouseId, $itemId);
        $warehouses = Warehouse::where('is_active', true)->orderBy('code')->get();
        $items = InventoryItem::where('is_active', true)->orderBy('code')->get();
        $totalValue = $report->sum('value');
        return view('inventory-valuation::valuation', compact('report', 'warehouses', 'items', 'totalValue', 'warehouseId', 'itemId'));
    }

    public function movements(Request $request): View
    {
        $query = InventoryMovement::with(['warehouse', 'item'])
            ->orderByDesc('movement_date')->orderByDesc('id');
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->integer('item_id'));
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->string('movement_type'));
        }
        $movements = $query->paginate(20);
        $warehouses = Warehouse::where('is_active', true)->orderBy('code')->get();
        $items = InventoryItem::where('is_active', true)->orderBy('code')->get();
        return view('inventory-valuation::movements.index', compact('movements', 'warehouses', 'items'));
    }

    public function movementCreate(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('code')->get();
        $items = InventoryItem::where('is_active', true)->orderBy('code')->get();
        return view('inventory-valuation::movements.create', compact('warehouses', 'items'));
    }

    public function movementStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'item_id' => ['required', 'exists:inventory_items,id'],
            'movement_type' => ['required', 'in:receipt,issue,transfer_in,transfer_out,adjustment,write_off'],
            'quantity' => ['required', 'numeric'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $this->valuation->recordMovement(
            (int) $data['warehouse_id'],
            (int) $data['item_id'],
            $data['movement_type'],
            (float) $data['quantity'],
            (float) ($data['unit_cost'] ?? 0),
            $data['reference'] ?? null,
            $data['movement_date'],
            $data['notes'] ?? null,
        );
        return redirect()->route('inventory-valuation.movements.index')->with('success', __('Movement recorded.'));
    }

    public function adjustments(Request $request): View
    {
        $query = InventoryMovement::with(['warehouse', 'item'])
            ->whereIn('movement_type', ['adjustment', 'write_off'])
            ->orderByDesc('movement_date');
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }
        $adjustments = $query->paginate(20);
        $warehouses = Warehouse::where('is_active', true)->orderBy('code')->get();
        return view('inventory-valuation::adjustments.index', compact('adjustments', 'warehouses'));
    }

    public function adjustmentCreate(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('code')->get();
        $items = InventoryItem::where('is_active', true)->orderBy('code')->get();
        return view('inventory-valuation::adjustments.create', compact('warehouses', 'items'));
    }

    public function adjustmentStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'item_id' => ['required', 'exists:inventory_items,id'],
            'type' => ['required', 'in:adjustment,write_off'],
            'quantity' => ['required', 'numeric'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
            'movement_date' => ['required', 'date'],
        ]);
        $qty = (float) $data['quantity'];
        if ($data['type'] === 'write_off' && $qty > 0) {
            $qty = -$qty;
        }
        $this->valuation->recordMovement(
            (int) $data['warehouse_id'],
            (int) $data['item_id'],
            $data['type'],
            $qty,
            (float) ($data['unit_cost'] ?? 0),
            null,
            $data['movement_date'],
            $data['reason'] ?? null,
        );
        return redirect()->route('inventory-valuation.adjustments.index')->with('success', __('Adjustment recorded.'));
    }

    public function warehouses(): View
    {
        $warehouses = Warehouse::orderBy('code')->paginate(20);
        return view('inventory-valuation::warehouses.index', compact('warehouses'));
    }

    public function warehouseCreate(): View
    {
        return view('inventory-valuation::warehouses.create');
    }

    public function warehouseStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        Warehouse::create($data);
        return redirect()->route('inventory-valuation.warehouses.index')->with('success', __('Warehouse created.'));
    }

    public function items(): View
    {
        $items = InventoryItem::orderBy('code')->paginate(20);
        return view('inventory-valuation::items.index', compact('items'));
    }

    public function itemCreate(): View
    {
        return view('inventory-valuation::items.create');
    }

    public function itemStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:20'],
            'valuation_method' => ['nullable', 'in:weighted_avg,fifo'],
        ]);
        $data['unit'] = $data['unit'] ?? 'EA';
        $data['valuation_method'] = $data['valuation_method'] ?? 'weighted_avg';
        InventoryItem::create($data);
        return redirect()->route('inventory-valuation.items.index')->with('success', __('Item created.'));
    }
}
