<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\ApprovalWorkflows\Infrastructure\Models\Approval;
use App\Modules\ApprovalWorkflows\Application\ApprovalWorkflowService;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CostingEngine\Application\AllocationService;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRun;
use App\Modules\AccountsPayable\Application\BillService;
use App\Modules\AccountsReceivable\Application\InvoiceService;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillAdjustment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceAdjustment;
use App\Core\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ApprovalWorkflowsController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $approvalWorkflows,
        protected JournalService $journalService,
        protected AllocationService $allocationService,
        protected BillService $billService,
        protected InvoiceService $invoiceService,
        protected AuditService $audit
    ) {}

    public function index(): View
    {
        $pendingVendorBills = ApBill::query()
            ->where('status', 'pending_approval')
            ->count();

        return view('lfs-administration::approval-workflows.index', [
            'pending_vendor_bills' => $pendingVendorBills,
        ]);
    }

    public function vendorBills(Request $request): View
    {
        $filters = $request->validate([
            'vendor_id' => ['nullable', 'integer', 'min:1'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        if (! empty($filters['from_date']) && ! empty($filters['to_date'])) {
            if (\Carbon\Carbon::parse($filters['from_date'])->gt(\Carbon\Carbon::parse($filters['to_date']))) {
                abort(422, 'Invalid date range: to_date must be >= from_date.');
            }
        }

        $query = ApBill::query()
            ->with('vendor')
            ->where('status', 'pending_approval');

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', (int) $filters['vendor_id']);
        }
        if (! empty($filters['from_date'])) {
            $query->whereDate('bill_date', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('bill_date', '<=', $filters['to_date']);
        }

        $bills = $query
            ->orderByDesc('bill_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('lfs-administration::approval-workflows.vendor-bills.index', [
            'bills' => $bills,
            'filters' => $filters,
        ]);
    }

    public function vendorBillShow(int $billId): View
    {
        $bill = ApBill::query()
            ->with('vendor', 'purchaseOrder')
            ->findOrFail($billId);

        if (! $bill->isPendingApproval()) {
            return view('lfs-administration::approval-workflows.empty', [
                'title' => 'Vendor Bill Approval',
                'note' => 'This vendor bill is not currently pending approval.',
            ]);
        }

        $approval = Approval::query()
            ->where('approvable_type', $bill->getMorphClass())
            ->where('approvable_id', $bill->getKey())
            ->where('approval_type', 'ap_bill')
            ->first();

        return view('lfs-administration::approval-workflows.vendor-bills.show', [
            'bill' => $bill,
            'approval' => $approval,
        ]);
    }

    public function journals(): View
    {
        $journals = Journal::query()
            ->where('status', 'pending_approval')
            ->with('lines.account')
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('lfs-administration::approval-workflows.journals.index', [
            'journals' => $journals,
        ]);
    }

    public function journalShow(int $id): View
    {
        $journal = Journal::query()
            ->with('lines.account', 'postingSource')
            ->findOrFail($id);

        $approval = Approval::query()
            ->where('approvable_type', $journal->getMorphClass())
            ->where('approvable_id', $journal->getKey())
            ->where('approval_type', 'journal')
            ->first();

        return view('lfs-administration::approval-workflows.journals.show', [
            'journal' => $journal,
            'approval' => $approval,
        ]);
    }

    public function journalApprove(int $id, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $journal = Journal::query()->findOrFail($id);
        if (! $journal->isPendingApproval()) {
            return redirect()->route('lfs-administration.approval-workflows.journals.show', $id)->with('error', __('Only journals pending approval can be approved.'));
        }

        $comments = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $journal,
            approvalType: 'journal',
            requestedBy: $userId,
            comments: $comments['comments'] ?? null,
            metadata: ['source' => 'ui.journalApprove']
        );
        $this->approvalWorkflows->approve($approval, $userId, $comments['comments'] ?? null);

        // Enforce gating at posting time.
        $this->journalService->postExistingJournal($journal);

        return redirect()->route('lfs-administration.approval-workflows.journals.show', $id)->with('success', __('Journal approved and posted.'));
    }

    public function journalReject(int $id, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $journal = Journal::query()->findOrFail($id);
        if (! $journal->isPendingApproval()) {
            return redirect()->route('lfs-administration.approval-workflows.journals.show', $id)->with('error', __('Only journals pending approval can be rejected.'));
        }

        $comments = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $journal,
            approvalType: 'journal',
            requestedBy: $userId,
            comments: $comments['comments'] ?? null,
            metadata: ['source' => 'ui.journalReject']
        );
        $this->approvalWorkflows->reject($approval, $userId, $comments['comments'] ?? null);

        $journal->update(['status' => 'rejected']);

        return redirect()->route('lfs-administration.approval-workflows.journals.show', $id)->with('success', __('Journal rejected.'));
    }

    public function invoices(): View
    {
        return view('lfs-administration::approval-workflows.empty', [
            'title' => 'Invoice Approval',
            'note' => 'Invoice approval queues are not fully enabled yet. (Pending approval workflow will be wired in a later domain-gating step.)',
        ]);
    }

    public function invoiceShow(int $id): View
    {
        return view('lfs-administration::approval-workflows.empty', [
            'title' => 'Invoice Approval',
            'note' => 'Invoice approval details are not enabled yet. (Pending approval workflow will be wired in a later domain-gating step.)',
        ]);
    }

    public function invoiceApprove(int $id, Request $request): RedirectResponse
    {
        return redirect()->route('lfs-administration.approval-workflows.invoices')->with('error', __('Invoice approvals are not enabled yet.'));
    }

    public function invoiceReject(int $id, Request $request): RedirectResponse
    {
        return redirect()->route('lfs-administration.approval-workflows.invoices')->with('error', __('Invoice approvals are not enabled yet.'));
    }

    public function allocations(): View
    {
        $runs = CostingAllocationRun::query()
            ->where('status', 'pending_approval')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('lfs-administration::approval-workflows.allocations.index', [
            'runs' => $runs,
        ]);
    }

    public function allocationShow(int $id): View
    {
        $run = CostingAllocationRun::query()->findOrFail($id);

        $approval = Approval::query()
            ->where('approvable_type', $run->getMorphClass())
            ->where('approvable_id', $run->getKey())
            ->where('approval_type', 'allocation')
            ->first();

        return view('lfs-administration::approval-workflows.allocations.show', [
            'run' => $run,
            'approval' => $approval,
        ]);
    }

    public function allocationApprove(int $id, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $run = CostingAllocationRun::query()->findOrFail($id);
        if (! $run->isPendingApproval()) {
            return redirect()
                ->route('lfs-administration.approval-workflows.allocations.show', $id)
                ->with('error', __('Only pending allocation runs can be approved.'));
        }

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $run,
            approvalType: 'allocation',
            requestedBy: $userId,
            comments: $data['comments'] ?? null,
            metadata: ['source' => 'ui.allocation-queue', 'run_id' => $run->id]
        );
        $this->approvalWorkflows->approve($approval, $userId, $data['comments'] ?? null);

        // Transition and apply within our own domain transaction boundary.
        $run->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        $this->allocationService->applyRulesForRun($run);

        return redirect()->route('costing-engine.allocation-engine')->with('success', __('Allocation run approved and applied.'));
    }

    public function allocationReject(int $id, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $run = CostingAllocationRun::query()->findOrFail($id);
        if (! $run->isPendingApproval()) {
            return redirect()
                ->route('lfs-administration.approval-workflows.allocations.show', $id)
                ->with('error', __('Only pending allocation runs can be rejected.'));
        }

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $run,
            approvalType: 'allocation',
            requestedBy: $userId,
            comments: $data['comments'] ?? null,
            metadata: ['source' => 'ui.allocation-queue', 'run_id' => $run->id]
        );
        $this->approvalWorkflows->reject($approval, $userId, $data['comments'] ?? null);

        $run->update([
            'status' => 'rejected',
            'rejected_by' => $userId,
            'rejected_at' => now(),
        ]);

        return redirect()->route('costing-engine.allocation-engine')->with('success', __('Allocation run rejected.'));
    }

    public function creditNotes(): View
    {
        $approvals = Approval::query()
            ->where('approval_type', 'credit_note')
            ->where('status', Approval::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('lfs-administration::approval-workflows.credit-notes.index', [
            'approvals' => $approvals,
        ]);
    }

    public function creditNoteShow(int $id): View
    {
        $approval = Approval::query()
            ->with('approvable')
            ->findOrFail($id);

        return view('lfs-administration::approval-workflows.credit-notes.show', [
            'approval' => $approval,
            'adjustment' => $approval->approvable,
        ]);
    }

    public function creditNoteApprove(int $id, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $approval = Approval::query()->with('approvable')->findOrFail($id);

        if ($approval->approval_type !== 'credit_note' || $approval->status !== Approval::STATUS_PENDING) {
            return redirect()->route('lfs-administration.approval-workflows.credit-notes')->with('error', __('This credit note approval is not pending.'));
        }

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->approvalWorkflows->approve($approval, $userId, $data['comments'] ?? null);

        $adjustment = $approval->approvable;
        if ($adjustment instanceof ArInvoiceAdjustment) {
            $this->invoiceService->approveCreditNoteRequest($adjustment, []);
        } elseif ($adjustment instanceof ApBillAdjustment) {
            $this->billService->approveCreditNoteRequest($adjustment, []);
        }

        return redirect()->route('lfs-administration.approval-workflows.credit-notes')->with('success', __('Credit note approved.'));
    }

    public function creditNoteReject(int $id, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $approval = Approval::query()->with('approvable')->findOrFail($id);

        if ($approval->approval_type !== 'credit_note' || $approval->status !== Approval::STATUS_PENDING) {
            return redirect()->route('lfs-administration.approval-workflows.credit-notes')->with('error', __('This credit note approval is not pending.'));
        }

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->approvalWorkflows->reject($approval, $userId, $data['comments'] ?? null);

        $adjustment = $approval->approvable;
        if ($adjustment instanceof ArInvoiceAdjustment) {
            $this->invoiceService->rejectCreditNoteRequest($adjustment);
        } elseif ($adjustment instanceof ApBillAdjustment) {
            $this->billService->rejectCreditNoteRequest($adjustment);
        }

        return redirect()->route('lfs-administration.approval-workflows.credit-notes')->with('success', __('Credit note rejected.'));
    }

    public function vendorBillApprove(int $billId): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $bill = ApBill::findOrFail($billId);
        if (! $bill->isPendingApproval()) {
            return redirect()->route('accounts-payable.bills.show', $billId)->with('error', __('Only pending bills can be approved.'));
        }

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $bill,
            approvalType: 'ap_bill',
            requestedBy: $userId,
            comments: null,
            metadata: ['source' => 'ui.vendor-bills']
        );
        $this->approvalWorkflows->approve($approval, $userId, null);

        // Keep existing AP behavior for posting side effects/audit event:
        $bill->update(['status' => 'approved']);
        $this->audit->logFinancial(
            description: 'AP bill approved (via Approval Workflows UI)',
            subject: $bill,
            properties: ['bill_number' => $bill->bill_number],
            event: 'ap.bill.approved'
        );

        return redirect()->route('accounts-payable.bills.show', $billId)->with('success', __('Bill approved. You can now issue it.'));
    }

    public function vendorBillReject(int $billId, Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $bill = ApBill::findOrFail($billId);
        if (! $bill->isPendingApproval()) {
            return redirect()->route('accounts-payable.bills.show', $billId)->with('error', __('Only pending bills can be rejected.'));
        }

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval = $this->approvalWorkflows->requestApproval(
            approvable: $bill,
            approvalType: 'ap_bill',
            requestedBy: $userId,
            comments: $data['comments'] ?? null,
            metadata: ['source' => 'ui.vendor-bills']
        );
        $this->approvalWorkflows->reject($approval, $userId, $data['comments'] ?? null);

        $bill->update(['status' => 'draft']);
        $this->audit->logFinancial(
            description: 'AP bill rejected back to draft (via Approval Workflows UI)',
            subject: $bill,
            properties: ['bill_number' => $bill->bill_number],
            event: 'ap.bill.rejected'
        );

        return redirect()->route('accounts-payable.bills.show', $billId)->with('success', __('Bill rejected back to draft.'));
    }
}

