<?php

namespace Tests\Feature\Procurement;

use App\Core\Services\AuditService;
use App\Models\Activity;
use App\Models\User;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequest;
use Database\Seeders\ModulePermissionsSeeder;
use Database\Seeders\VendorsSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProcurementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function procurementUser(): User
    {
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    private function viewOnlyUser(): User
    {
        Permission::findOrCreate('procurement.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('procurement.view');

        return $user;
    }

    public function test_procurement_index_forbidden_without_view_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('procurement.index'))
            ->assertForbidden();
    }

    public function test_procurement_index_ok_with_view_permission(): void
    {
        $user = $this->viewOnlyUser();
        $this->actingAs($user)
            ->get(route('procurement.index'))
            ->assertOk();
    }

    public function test_create_pr_forbidden_without_manage(): void
    {
        $user = $this->viewOnlyUser();
        $this->actingAs($user)
            ->get(route('procurement.purchase-requests.create'))
            ->assertForbidden();
    }

    public function test_full_pr_po_lifecycle_and_audit(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(VendorsSeeder::class);
        $user = $this->procurementUser();
        $this->actingAs($user);

        $vendor = Vendor::query()->where('code', 'V001')->first();
        $this->assertNotNull($vendor);

        $this->post(route('procurement.purchase-requests.store'), [
            'requested_by' => 'Alice',
            'department' => 'Ops',
            'request_date' => now()->toDateString(),
            'notes' => 'Need parts',
            'lines' => [
                ['description' => 'Widget A', 'quantity' => 2, 'estimated_unit_cost' => 10.5, 'account_code' => '5000'],
            ],
        ])->assertRedirect();

        $pr = PurchaseRequest::query()->latest('id')->first();
        $this->assertNotNull($pr);
        $this->assertSame(PurchaseRequest::STATUS_DRAFT, $pr->status);

        $this->assertTrue(
            Activity::query()
                ->where('log_name', AuditService::LOG_PROCUREMENT)
                ->where('event', 'procurement.pr.created')
                ->where('subject_type', PurchaseRequest::class)
                ->where('subject_id', $pr->id)
                ->exists()
        );

        $this->post(route('procurement.purchase-requests.submit', $pr->id))->assertRedirect();
        $pr->refresh();
        $this->assertSame(PurchaseRequest::STATUS_SUBMITTED, $pr->status);

        $this->post(route('procurement.purchase-requests.approve', $pr->id))->assertRedirect();
        $pr->refresh();
        $this->assertSame(PurchaseRequest::STATUS_APPROVED, $pr->status);

        $this->post(route('procurement.purchase-orders.store'), [
            'vendor_id' => $vendor->id,
            'purchase_request_id' => $pr->id,
            'order_date' => now()->toDateString(),
            'currency' => 'USD',
            'lines' => [
                [
                    'description' => 'Widget A',
                    'quantity' => 2,
                    'unit_price' => 10.5,
                    'account_code' => '5000',
                    'purchase_request_line_id' => $pr->lines->first()->id,
                ],
            ],
        ])->assertRedirect();

        $po = PurchaseOrder::query()->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $po->status);
        $this->assertSame((float) 21, (float) $po->total);
        $this->assertNotNull($po->lines->first()->purchase_request_line_id);

        $this->assertTrue(
            Activity::query()
                ->where('event', 'procurement.po.created')
                ->where('subject_id', $po->id)
                ->exists()
        );

        $this->post(route('procurement.purchase-orders.issue', $po->id))->assertRedirect();
        $po->refresh();
        $this->assertSame(PurchaseOrder::STATUS_ISSUED, $po->status);

        $this->post(route('procurement.purchase-orders.receive', $po->id))->assertRedirect();
        $po->refresh();
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $po->status);
        $this->assertNotNull($po->received_date);
    }

    public function test_cannot_submit_non_draft_pr(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->procurementUser();
        $this->actingAs($user);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-TEST-00001',
            'request_date' => now(),
            'status' => PurchaseRequest::STATUS_SUBMITTED,
        ]);

        $this->post(route('procurement.purchase-requests.submit', $pr->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_approve_non_submitted_pr(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->procurementUser();
        $this->actingAs($user);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-TEST-00003',
            'request_date' => now(),
            'status' => PurchaseRequest::STATUS_DRAFT,
        ]);

        $this->post(route('procurement.purchase-requests.approve', $pr->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_issue_non_draft_po(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(VendorsSeeder::class);
        $user = $this->procurementUser();
        $this->actingAs($user);
        $vendor = Vendor::query()->first();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-00002',
            'vendor_id' => $vendor->id,
            'order_date' => now(),
            'status' => PurchaseOrder::STATUS_ISSUED,
            'total' => 0,
            'currency' => 'USD',
        ]);

        $this->post(route('procurement.purchase-orders.issue', $po->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_receive_non_issued_po(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(VendorsSeeder::class);
        $user = $this->procurementUser();
        $this->actingAs($user);
        $vendor = Vendor::query()->first();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-00003',
            'vendor_id' => $vendor->id,
            'order_date' => now(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'total' => 0,
            'currency' => 'USD',
        ]);

        $this->post(route('procurement.purchase-orders.receive', $po->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_po_rejects_non_approved_pr_link(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(VendorsSeeder::class);
        $user = $this->procurementUser();
        $this->actingAs($user);
        $vendor = Vendor::query()->first();

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-TEST-00002',
            'request_date' => now(),
            'status' => PurchaseRequest::STATUS_DRAFT,
        ]);

        $this->post(route('procurement.purchase-orders.store'), [
            'vendor_id' => $vendor->id,
            'purchase_request_id' => $pr->id,
            'order_date' => now()->toDateString(),
            'currency' => 'USD',
            'lines' => [
                ['description' => 'X', 'quantity' => 1, 'unit_price' => 1],
            ],
        ])->assertSessionHasErrors('purchase_request_id');
    }

    public function test_ap_bill_create_from_po_smoke(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(VendorsSeeder::class);
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['procurement.view', 'procurement.manage', 'accounts-payable.view', 'accounts-payable.manage']);

        $vendor = Vendor::query()->first();
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-00001',
            'vendor_id' => $vendor->id,
            'order_date' => now(),
            'status' => PurchaseOrder::STATUS_ISSUED,
            'total' => 100,
            'currency' => 'USD',
        ]);

        $this->actingAs($user)
            ->get(route('accounts-payable.bills.create', ['purchase_order_id' => $po->id]))
            ->assertOk();
    }
}
