<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\ApprovalWorkflows\Application\ApprovalWorkflowService;
use App\Modules\ApprovalWorkflows\Infrastructure\Models\Approval;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CostingEngine\Application\AllocationService;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRun;
use App\Modules\AccountsPayable\Application\BillService;
use App\Modules\AccountsReceivable\Application\InvoiceService;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillAdjustment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowsApiController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $approvalWorkflows,
        protected JournalService $journalService,
        protected AllocationService $allocationService,
        protected BillService $billService,
        protected InvoiceService $invoiceService,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'counts' => [
                'vendor_bills_pending' => ApBill::query()->where('status', 'pending_approval')->count(),
                'journals_pending' => Journal::query()->where('status', 'pending_approval')->count(),
                'allocations_pending' => CostingAllocationRun::query()->where('status', 'pending_approval')->count(),
                'credit_notes_pending' => Approval::query()
                    ->where('approval_type', 'credit_note')
                    ->where('status', Approval::STATUS_PENDING)
                    ->count(),
            ],
        ]);
    }

    public function queue(string $type, Request $request): JsonResponse
    {
        $data = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = (int) ($data['per_page'] ?? 50);

        return match ($type) {
            'vendor-bills' => $this->vendorBillsQueue($request, $perPage),
            'journals' => $this->journalsQueue($perPage),
            'allocations' => $this->allocationsQueue($perPage),
            'credit-notes' => $this->creditNotesQueue($perPage),
            default => throw ValidationException::withMessages(['type' => ['Invalid queue type.']]),
        };
    }

    public function approve(int $approvalId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = (int) $request->user()?->id;
        abort_unless($userId, 403);

        $approval = Approval::query()->with('approvable')->findOrFail($approvalId);
        if (! $approval->isPending()) {
            throw ValidationException::withMessages(['approval' => ['Only pending approvals can be approved.']]);
        }

        $this->approvalWorkflows->approve($approval, $userId, $data['comments'] ?? null);

        $approvable = $approval->approvable;
        if ($approval->approval_type === 'journal' && $approvable instanceof Journal) {
            $this->journalService->postExistingJournal($approvable);
        } elseif ($approval->approval_type === 'allocation' && $approvable instanceof CostingAllocationRun) {
            $approvable->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
            $this->allocationService->applyRulesForRun($approvable);
        } elseif ($approval->approval_type === 'credit_note') {
            if ($approvable instanceof ArInvoiceAdjustment) {
                $this->invoiceService->approveCreditNoteRequest($approvable, []);
            } elseif ($approvable instanceof ApBillAdjustment) {
                $this->billService->approveCreditNoteRequest($approvable, []);
            }
        }

        return response()->json(['success' => true]);
    }

    public function reject(int $approvalId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = (int) $request->user()?->id;
        abort_unless($userId, 403);

        $approval = Approval::query()->with('approvable')->findOrFail($approvalId);
        if (! $approval->isPending()) {
            throw ValidationException::withMessages(['approval' => ['Only pending approvals can be rejected.']]);
        }

        $this->approvalWorkflows->reject($approval, $userId, $data['comments'] ?? null);

        $approvable = $approval->approvable;
        if ($approval->approval_type === 'journal' && $approvable instanceof Journal) {
            $approvable->update(['status' => 'rejected']);
        } elseif ($approval->approval_type === 'allocation' && $approvable instanceof CostingAllocationRun) {
            $approvable->update([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'rejected_at' => now(),
            ]);
        } elseif ($approval->approval_type === 'credit_note') {
            if ($approvable instanceof ArInvoiceAdjustment) {
                $this->invoiceService->rejectCreditNoteRequest($approvable);
            } elseif ($approvable instanceof ApBillAdjustment) {
                $this->billService->rejectCreditNoteRequest($approvable);
            }
        }

        return response()->json(['success' => true]);
    }

    private function vendorBillsQueue(Request $request, int $perPage): JsonResponse
    {
        $filters = $request->validate([
            'vendor_id' => ['nullable', 'integer', 'min:1'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        if (! empty($filters['from_date']) && ! empty($filters['to_date']) && \Carbon\Carbon::parse($filters['from_date'])->gt(\Carbon\Carbon::parse($filters['to_date']))) {
            throw ValidationException::withMessages(['to_date' => ['The to_date must be greater than or equal to from_date.']]);
        }

        $query = ApBill::query()
            ->with('vendor')
            ->select(['id', 'vendor_id', 'bill_number', 'bill_date', 'due_date', 'status', 'total', 'currency'])
            ->where('status', 'pending_approval')
            ->orderByDesc('bill_date')
            ->orderByDesc('id');

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', (int) $filters['vendor_id']);
        }
        if (! empty($filters['from_date'])) {
            $query->whereDate('bill_date', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('bill_date', '<=', $filters['to_date']);
        }

        $paginator = $query->paginate($perPage)->withQueryString();
        $items = $paginator->getCollection()->map(function (ApBill $bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'bill_date' => $bill->bill_date?->toDateString(),
                'due_date' => $bill->due_date?->toDateString(),
                'status' => $bill->status,
                'total' => (float) $bill->total,
                'currency' => $bill->currency,
                'vendor' => [
                    'id' => $bill->vendor?->id,
                    'code' => $bill->vendor?->code,
                    'name' => $bill->vendor?->name,
                ],
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function journalsQueue(int $perPage): JsonResponse
    {
        $query = Journal::query()
            ->select(['id', 'journal_number', 'journal_date', 'period', 'description', 'status'])
            ->where('status', 'pending_approval')
            ->orderByDesc('journal_date')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->withQueryString();
        $items = $paginator->getCollection()->map(function (Journal $j) {
            return [
                'id' => $j->id,
                'journal_number' => $j->journal_number,
                'journal_date' => $j->journal_date?->toDateString(),
                'period' => $j->period,
                'description' => $j->description,
                'status' => $j->status,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function allocationsQueue(int $perPage): JsonResponse
    {
        $query = CostingAllocationRun::query()
            ->select(['id', 'run_date', 'status', 'requested_by', 'requested_at', 'created_at'])
            ->where('status', 'pending_approval')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->withQueryString();
        $items = $paginator->getCollection()->map(function (CostingAllocationRun $run) {
            return [
                'id' => $run->id,
                'run_date' => $run->run_date?->toDateString(),
                'status' => $run->status,
                'requested_by' => $run->requested_by,
                'requested_at' => $run->requested_at?->toIso8601String(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function creditNotesQueue(int $perPage): JsonResponse
    {
        $query = Approval::query()
            ->with('approvable')
            ->select(['id', 'approvable_type', 'approvable_id', 'approval_type', 'status', 'requested_at', 'created_at'])
            ->where('approval_type', 'credit_note')
            ->where('status', Approval::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->withQueryString();
        $items = $paginator->getCollection()->map(function (Approval $approval) {
            $adjustment = $approval->approvable;

            $ref = null;
            $amount = null;
            $adjustmentNumber = null;
            $adjustmentStatus = null;

            if ($adjustment instanceof ArInvoiceAdjustment) {
                $ref = $adjustment->invoice?->invoice_number;
                $amount = abs((float) $adjustment->amount);
                $adjustmentNumber = $adjustment->adjustment_number;
                $adjustmentStatus = $adjustment->status;
            } elseif ($adjustment instanceof ApBillAdjustment) {
                $ref = $adjustment->bill?->bill_number;
                $amount = abs((float) $adjustment->amount);
                $adjustmentNumber = $adjustment->adjustment_number;
                $adjustmentStatus = $adjustment->status;
            }

            return [
                'id' => $approval->id,
                'status' => $approval->status,
                'requested_at' => $approval->requested_at?->toIso8601String(),
                'reference' => $ref,
                'adjustment_number' => $adjustmentNumber,
                'adjustment_amount' => $amount,
                'adjustment_status' => $adjustmentStatus,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

