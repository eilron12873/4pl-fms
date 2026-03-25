<?php

namespace Tests\Unit\ApprovalWorkflows;

use App\Core\Services\AuditService;
use App\Models\User;
use App\Modules\ApprovalWorkflows\Application\ApprovalWorkflowService;
use App\Modules\ApprovalWorkflows\Infrastructure\Models\Approval;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_approval_is_idempotent(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $vendor = Vendor::create(['code' => 'V-UT', 'name' => 'Vendor UT', 'currency' => 'USD', 'payment_terms_days' => 30, 'is_active' => true]);
        $bill = ApBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'AP-UT-0001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_allocated' => 0,
            'currency' => 'USD',
        ]);

        $svc = app(ApprovalWorkflowService::class);
        $a1 = $svc->requestApproval($bill, 'ap_bill', $user->id);
        $a2 = $svc->requestApproval($bill, 'ap_bill', $user->id);

        $this->assertSame($a1->id, $a2->id);
        $this->assertSame(1, Approval::query()->count());
        $this->assertSame(Approval::STATUS_PENDING, $a1->status);
    }

    public function test_approve_is_idempotent_and_reject_after_approve_is_blocked(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $approval = Approval::create([
            'approvable_type' => ApBill::class,
            'approvable_id' => 1,
            'approval_type' => 'ap_bill',
            'status' => Approval::STATUS_PENDING,
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);

        $svc = app(ApprovalWorkflowService::class);
        $approved1 = $svc->approve($approval, $user->id, 'ok');
        $approved2 = $svc->approve($approval, $user->id, 'ok again');

        $this->assertSame(Approval::STATUS_APPROVED, $approved1->status);
        $this->assertSame($approved1->id, $approved2->id);
        $this->assertSame(Approval::STATUS_APPROVED, $approved2->status);

        $this->expectException(\InvalidArgumentException::class);
        $svc->reject($approval, $user->id, 'nope');
    }
}

