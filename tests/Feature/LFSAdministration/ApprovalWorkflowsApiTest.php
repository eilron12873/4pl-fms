<?php

namespace Tests\Feature\LFSAdministration;

use App\Models\User;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\ApprovalWorkflows\Infrastructure\Models\Approval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ApprovalWorkflowsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_auth(): void
    {
        $this->getJson('/api/lfs-administration/approval-workflows')
            ->assertStatus(401);
    }

    public function test_queue_rejects_invalid_type(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $view = Permission::findOrCreate('lfs-administration.view', 'sanctum');
        $user->givePermissionTo($view);

        Sanctum::actingAs($user);
        $this
            ->getJson('/api/lfs-administration/approval-workflows/queue/invalid')
            ->assertStatus(422);
    }

    public function test_vendor_bills_queue_orders_deterministically(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $view = Permission::findOrCreate('lfs-administration.view', 'sanctum');
        $user->givePermissionTo($view);
        Sanctum::actingAs($user);

        $vendor = Vendor::create(['code' => 'V-API', 'name' => 'Vendor API', 'currency' => 'USD', 'payment_terms_days' => 30, 'is_active' => true]);

        $b1 = ApBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'AP-API-0001',
            'bill_date' => '2026-01-02',
            'due_date' => '2026-02-01',
            'status' => 'pending_approval',
            'subtotal' => 10,
            'tax_amount' => 0,
            'total' => 10,
            'amount_allocated' => 0,
            'currency' => 'USD',
        ]);
        $b2 = ApBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'AP-API-0002',
            'bill_date' => '2026-01-03',
            'due_date' => '2026-02-02',
            'status' => 'pending_approval',
            'subtotal' => 20,
            'tax_amount' => 0,
            'total' => 20,
            'amount_allocated' => 0,
            'currency' => 'USD',
        ]);

        $resp = $this->getJson('/api/lfs-administration/approval-workflows/queue/vendor-bills?per_page=50')
            ->assertOk()
            ->json();

        $this->assertTrue($resp['success']);
        $ids = array_map(fn ($i) => $i['id'], $resp['items']);
        $this->assertSame([$b2->id, $b1->id], $ids);
    }

    public function test_approve_endpoint_requires_manage_permission(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $view = Permission::findOrCreate('lfs-administration.view', 'sanctum');
        Permission::findOrCreate('lfs-administration.manage', 'sanctum');
        $user->givePermissionTo($view);

        $approval = Approval::create([
            'approvable_type' => ApBill::class,
            'approvable_id' => 1,
            'approval_type' => 'credit_note',
            'status' => Approval::STATUS_PENDING,
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);

        Sanctum::actingAs($user);
        $this
            ->postJson('/api/lfs-administration/approval-workflows/' . $approval->id . '/approve', [])
            ->assertStatus(403);
    }
}

