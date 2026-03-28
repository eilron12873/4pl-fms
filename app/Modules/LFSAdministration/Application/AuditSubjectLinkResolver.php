<?php

namespace App\Modules\LFSAdministration\Application;

use App\Models\User;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBillAdjustment;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoiceAdjustment;
use App\Modules\ApprovalWorkflows\Infrastructure\Models\Approval;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CostingEngine\Infrastructure\Models\CostingAllocationRun;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequest;
use Spatie\Permission\Models\Role;

class AuditSubjectLinkResolver
{
    public function resolveUrl(?string $subjectType, ?int $subjectId): ?string
    {
        if ($subjectType === null || $subjectId === null) {
            return null;
        }

        return match ($subjectType) {
            PurchaseRequest::class => route('procurement.purchase-requests.show', $subjectId),
            PurchaseOrder::class => route('procurement.purchase-orders.show', $subjectId),
            User::class => route('lfs-administration.settings.users.edit', $subjectId),
            Journal::class => route('core-accounting.journals.show', $subjectId),
            ApBill::class => route('accounts-payable.bills.show', $subjectId),
            ArInvoice::class => route('accounts-receivable.invoices.show', $subjectId),
            CostingAllocationRun::class => route('lfs-administration.approval-workflows.allocations.show', $subjectId),
            Role::class => route('lfs-administration.roles.edit', $subjectId),
            Approval::class => $this->resolveApprovalLink($subjectId),
            ArInvoiceAdjustment::class => $this->resolveArInvoiceAdjustment($subjectId),
            ApBillAdjustment::class => $this->resolveApBillAdjustment($subjectId),
            default => null,
        };
    }

    private function resolveApprovalLink(int $approvalId): ?string
    {
        $approval = Approval::query()->find($approvalId);
        if ($approval === null) {
            return null;
        }

        $aid = (int) $approval->approvable_id;

        return match ($approval->approval_type) {
            'journal' => route('lfs-administration.approval-workflows.journals.show', $aid),
            'ap_bill' => route('lfs-administration.approval-workflows.vendor-bills.show', $aid),
            'ar_invoice' => route('accounts-receivable.invoices.show', $aid),
            'allocation' => route('lfs-administration.approval-workflows.allocations.show', $aid),
            'credit_note' => route('lfs-administration.approval-workflows.credit-notes.show', $approvalId),
            default => null,
        };
    }

    private function resolveArInvoiceAdjustment(int $id): ?string
    {
        $adj = ArInvoiceAdjustment::query()->find($id);

        return $adj ? route('accounts-receivable.invoices.show', $adj->invoice_id) : null;
    }

    private function resolveApBillAdjustment(int $id): ?string
    {
        $adj = ApBillAdjustment::query()->find($id);

        return $adj ? route('accounts-payable.bills.show', $adj->bill_id) : null;
    }
}
