<?php

namespace Database\Seeders;

use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use App\Modules\Procurement\Infrastructure\Models\PurchaseOrderLine;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequest;
use App\Modules\Procurement\Infrastructure\Models\PurchaseRequestLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Sample purchase requests and orders for manual UI testing of the Procurement module.
 * Idempotent: skips if PR-SEED-00001 already exists.
 */
class ProcurementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(VendorsSeeder::class);

        if (PurchaseRequest::query()->where('pr_number', 'PR-SEED-00001')->exists()) {
            if ($this->command) {
                $this->command->info('Procurement demo data already present (PR-SEED-00001). Skipping.');
            }

            return;
        }

        $v001 = Vendor::query()->where('code', 'V001')->firstOrFail();
        $v002 = Vendor::query()->where('code', 'V002')->firstOrFail();

        $baseDate = Carbon::parse('now')->subDays(14)->startOfDay();

        // --- Purchase requests (various statuses) ---
        $prDraft = PurchaseRequest::create([
            'pr_number' => 'PR-SEED-00001',
            'requested_by' => 'Jamie Chen',
            'department' => 'Warehouse',
            'request_date' => $baseDate->copy()->addDays(2),
            'status' => PurchaseRequest::STATUS_DRAFT,
            'notes' => 'Demo: draft P.R. — submit from detail view.',
        ]);
        PurchaseRequestLine::create([
            'purchase_request_id' => $prDraft->id,
            'description' => 'Pallet wrap rolls (18 in)',
            'quantity' => 24,
            'estimated_unit_cost' => 42.5,
            'account_code' => '5100',
        ]);
        PurchaseRequestLine::create([
            'purchase_request_id' => $prDraft->id,
            'description' => 'Safety vests (L)',
            'quantity' => 15,
            'estimated_unit_cost' => 12,
            'account_code' => '5100',
        ]);

        $prSubmitted = PurchaseRequest::create([
            'pr_number' => 'PR-SEED-00002',
            'requested_by' => 'Alex Rivera',
            'department' => 'Operations',
            'request_date' => $baseDate->copy()->addDays(5),
            'status' => PurchaseRequest::STATUS_SUBMITTED,
            'notes' => 'Demo: submitted — approve from detail view.',
        ]);
        PurchaseRequestLine::create([
            'purchase_request_id' => $prSubmitted->id,
            'description' => 'Forklift battery maintenance kit',
            'quantity' => 2,
            'estimated_unit_cost' => 189,
            'account_code' => '5200',
        ]);

        $prApproved = PurchaseRequest::create([
            'pr_number' => 'PR-SEED-00003',
            'requested_by' => 'Morgan Lee',
            'department' => 'Facilities',
            'request_date' => $baseDate->copy()->addDays(7),
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approval_date' => $baseDate->copy()->addDays(8),
            'notes' => 'Demo: approved — use Create P.O. from this P.R. or link on P.O. create.',
        ]);
        $prLineA = PurchaseRequestLine::create([
            'purchase_request_id' => $prApproved->id,
            'description' => 'Industrial floor cleaner (20L)',
            'quantity' => 6,
            'estimated_unit_cost' => 55,
            'account_code' => '5300',
        ]);
        $prLineB = PurchaseRequestLine::create([
            'purchase_request_id' => $prApproved->id,
            'description' => 'Mop heads (commercial)',
            'quantity' => 20,
            'estimated_unit_cost' => 8.25,
            'account_code' => '5300',
        ]);

        $prApprovedStandalone = PurchaseRequest::create([
            'pr_number' => 'PR-SEED-00004',
            'requested_by' => 'Riley Kim',
            'department' => 'IT',
            'request_date' => $baseDate->copy()->addDays(10),
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approval_date' => $baseDate->copy()->addDays(11),
            'notes' => 'Demo: approved with no P.O. yet.',
        ]);
        PurchaseRequestLine::create([
            'purchase_request_id' => $prApprovedStandalone->id,
            'description' => 'Network patch cables Cat6 (5m), box of 50',
            'quantity' => 1,
            'estimated_unit_cost' => 320,
            'account_code' => '5400',
        ]);

        // --- Purchase orders ---
        $poDraftStandalone = PurchaseOrder::create([
            'po_number' => 'PO-SEED-00001',
            'vendor_id' => $v001->id,
            'purchase_request_id' => null,
            'order_date' => $baseDate->copy()->addDays(3),
            'expected_date' => $baseDate->copy()->addDays(20),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'total' => 1250,
            'currency' => 'USD',
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $poDraftStandalone->id,
            'purchase_request_line_id' => null,
            'description' => 'Drayage — inbound container unload',
            'quantity' => 5,
            'unit_price' => 250,
            'amount' => 1250,
            'account_code' => '6100',
        ]);

        $qty = 6.0;
        $price = 55.0;
        $amountA = $qty * $price;
        $qtyB = 20.0;
        $priceB = 8.25;
        $amountB = $qtyB * $priceB;
        $poDraftLinked = PurchaseOrder::create([
            'po_number' => 'PO-SEED-00002',
            'vendor_id' => $v002->id,
            'purchase_request_id' => $prApproved->id,
            'order_date' => $baseDate->copy()->addDays(9),
            'expected_date' => $baseDate->copy()->addDays(25),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'total' => $amountA + $amountB,
            'currency' => 'USD',
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $poDraftLinked->id,
            'purchase_request_line_id' => $prLineA->id,
            'description' => 'Industrial floor cleaner (20L)',
            'quantity' => $qty,
            'unit_price' => $price,
            'amount' => $amountA,
            'account_code' => '5300',
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $poDraftLinked->id,
            'purchase_request_line_id' => $prLineB->id,
            'description' => 'Mop heads (commercial)',
            'quantity' => $qtyB,
            'unit_price' => $priceB,
            'amount' => $amountB,
            'account_code' => '5300',
        ]);

        $poIssued = PurchaseOrder::create([
            'po_number' => 'PO-SEED-00003',
            'vendor_id' => $v001->id,
            'purchase_request_id' => null,
            'order_date' => $baseDate->copy()->addDays(4),
            'expected_date' => $baseDate->copy()->addDays(18),
            'status' => PurchaseOrder::STATUS_ISSUED,
            'total' => 480,
            'currency' => 'USD',
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $poIssued->id,
            'purchase_request_line_id' => null,
            'description' => 'Cross-dock handling — hourly',
            'quantity' => 16,
            'unit_price' => 30,
            'amount' => 480,
            'account_code' => '6100',
        ]);

        $poReceived = PurchaseOrder::create([
            'po_number' => 'PO-SEED-00004',
            'vendor_id' => $v002->id,
            'purchase_request_id' => null,
            'order_date' => $baseDate->copy()->addDays(1),
            'expected_date' => $baseDate->copy()->addDays(10),
            'received_date' => $baseDate->copy()->addDays(9),
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'total' => 975.5,
            'currency' => 'USD',
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $poReceived->id,
            'purchase_request_line_id' => null,
            'description' => 'Warehouse storage — monthly slot',
            'quantity' => 1,
            'unit_price' => 975.5,
            'amount' => 975.5,
            'account_code' => '6200',
        ]);

        if ($this->command) {
            $this->command->info('Procurement demo seeded: 4 P.R.s, 4 P.O.s (see PR-SEED-* / PO-SEED-*).');
        }
    }
}
