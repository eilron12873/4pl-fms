<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleLine;
use App\Modules\CoreAccounting\Infrastructure\Models\AccountResolver;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleCondition;
use Illuminate\Database\Seeder;

class PostingRulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedShipmentDeliveredRule();
        $this->seedStorageAccrualRule();
        $this->seedVendorInvoiceApprovedRule();
        $this->seedProjectMilestoneCompletedRule();
        $this->seedRevenueByServiceLineResolvers();
        $this->seedSubcontractedVendorInvoiceRule();
        $this->seedStorageDailyAccrualRule();
        $this->seedPodConfirmedRule();
        $this->seedFreightCostAccrualRule();
        $this->seedFuelExpenseRecordedRule();
        $this->seedClientInvoiceIssuedRule();
        $this->seedClientPaymentReceivedRule();
        $this->seedClientCreditNoteRule();
        $this->seedVendorPaymentProcessedRule();
        $this->seedPurchaseOrderReceivedRule();
        $this->seedInventoryAdjustmentRule();
        $this->seedAssetAcquisitionRule();
        $this->seedDepreciationPostingRule();
    }

    protected function seedShipmentDeliveredRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'shipment-delivered'],
            [
                'description' => 'Default posting for shipment delivered (AR vs freight revenue).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $arAccountId = $this->accountId('1100', 'Accounts Receivable', 'asset');
        $revenueAccountId = $this->accountId('4100', 'Freight Revenue', 'revenue');

        $this->createStandardLines(
            $rule,
            $arAccountId,
            $revenueAccountId,
            'amount',
            [
                'client_id' => 'payload.client_id',
                'shipment_id' => 'payload.shipment_id',
                'route_id' => 'payload.route_id',
            ],
        );
    }

    protected function seedRevenueByServiceLineResolvers(): void
    {
        // Configure dynamic revenue-by-service-line mapping for the credit line
        // of the shipment-delivered event. The default revenue account remains
        // as a fallback when no resolver matches.
        $rule = PostingRule::where('event_type', 'shipment-delivered')->first();
        if (! $rule) {
            return;
        }

        /** @var PostingRuleLine|null $creditLine */
        $creditLine = $rule->lines()->where('entry_type', 'credit')->first();
        if (! $creditLine) {
            return;
        }

        if (! $creditLine->resolver_type) {
            $creditLine->update(['resolver_type' => 'revenue_by_service_line']);
        }

        $this->ensureRevenueResolver('warehousing', '4200', 'Storage Revenue');
        $this->ensureRevenueResolver('transport', '4100', 'Freight Revenue');
        $this->ensureRevenueResolver('project_cargo', '4300', 'Project Revenue');
    }

    protected function seedSubcontractedVendorInvoiceRule(): void
    {
        // Example conditional rule: for vendor-invoice-approved with
        // shipment_type = subcontracted, post to Cost of Freight instead
        // of the default Transport Expense. For all other cases, the
        // original rule remains the fallback.
        $baseRule = PostingRule::where('event_type', 'vendor-invoice-approved')
            ->orderBy('id')
            ->first();

        if (! $baseRule) {
            return;
        }

        $conditionalRule = PostingRule::firstOrCreate(
            [
                'event_type' => 'vendor-invoice-approved',
                'description' => 'Vendor invoice approved (subcontracted shipment: cost of freight vs AP).',
            ],
            [
                'is_active' => true,
            ],
        );

        if (! $conditionalRule->lines()->exists()) {
            $costOfFreightId = $this->accountId('5300', 'Cost of Goods Sold', 'expense');
            $apAccountId = $this->accountId('2100', 'Accounts Payable', 'liability');

            $this->createStandardLines(
                $conditionalRule,
                $costOfFreightId,
                $apAccountId,
                'amount',
                [],
            );
        }

        if (! $conditionalRule->conditions()->exists()) {
            PostingRuleCondition::create([
                'posting_rule_id' => $conditionalRule->id,
                'field_name' => 'shipment_type',
                'operator' => '=',
                'comparison_value' => 'subcontracted',
                'priority' => 100,
            ]);
        }
    }

    protected function ensureRevenueResolver(string $serviceLine, string $accountCode, string $accountName): void
    {
        $accountId = $this->accountId($accountCode, $accountName, 'revenue');

        AccountResolver::firstOrCreate(
            [
                'resolver_type' => 'revenue_by_service_line',
                'dimension_key' => 'service_line',
                'dimension_value' => $serviceLine,
            ],
            [
                'account_id' => $accountId,
                'priority' => 100,
            ],
        );
    }

    protected function seedStorageAccrualRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'storage-accrual'],
            [
                'description' => 'Default posting for storage accrual (storage expense vs accrued liability/AP).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $expenseAccountId = $this->accountId('5100', 'Storage Expense', 'expense');
        $liabilityAccountId = $this->accountId('2100', 'Accounts Payable', 'liability');

        $this->createStandardLines(
            $rule,
            $expenseAccountId,
            $liabilityAccountId,
            'amount',
            [
                'client_id' => 'payload.client_id',
                'warehouse_id' => 'payload.warehouse_id',
            ],
        );
    }

    protected function seedVendorInvoiceApprovedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'vendor-invoice-approved'],
            [
                'description' => 'Default posting for vendor invoice approved (transport expense vs AP).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $expenseAccountId = $this->accountId('5200', 'Transport Expense', 'expense');
        $apAccountId = $this->accountId('2100', 'Accounts Payable', 'liability');

        $this->createStandardLines(
            $rule,
            $expenseAccountId,
            $apAccountId,
            'amount',
            [],
        );
    }

    protected function seedProjectMilestoneCompletedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'project-milestone-completed'],
            [
                'description' => 'Default posting for project milestone completed (AR/inventory vs project revenue).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $receivableAccountId = $this->accountId('1200', 'Inventory', 'asset');
        $revenueAccountId = $this->accountId('4300', 'Project Revenue', 'revenue');

        $this->createStandardLines(
            $rule,
            $receivableAccountId,
            $revenueAccountId,
            'amount',
            [
                'client_id' => 'payload.client_id',
                'project_id' => 'payload.project_id',
            ],
        );
    }

    protected function seedStorageDailyAccrualRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'storage-daily-accrual'],
            [
                'description' => 'Storage daily accrual (accrued revenue vs storage revenue).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $accruedRevenueId = $this->accountId('4205', 'Accrued Storage Revenue', 'revenue');
        $storageRevenueId = $this->accountId('4200', 'Storage Revenue', 'revenue');

        $this->createStandardLines(
            $rule,
            $accruedRevenueId,
            $storageRevenueId,
            'amount',
            [
                'client_id' => 'payload.client_id',
                'warehouse_id' => 'payload.warehouse_id',
            ],
        );
    }

    protected function seedPodConfirmedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'pod-confirmed'],
            [
                'description' => 'POD confirmed (AR vs delivery revenue).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $arAccountId = $this->accountId('1100', 'Accounts Receivable', 'asset');
        $deliveryRevenueId = $this->accountId('4110', 'Delivery Revenue', 'revenue');

        $this->createStandardLines(
            $rule,
            $arAccountId,
            $deliveryRevenueId,
            'amount',
            [
                'client_id' => 'payload.client_id',
                'shipment_id' => 'payload.shipment_id',
            ],
        );
    }

    protected function seedFreightCostAccrualRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'freight-cost-accrual'],
            [
                'description' => 'Freight cost accrual (freight expense vs accrued payables).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $freightExpenseId = $this->accountId('5200', 'Transport Expense', 'expense');
        $accruedPayablesId = $this->accountId('2200', 'Accrued Liabilities', 'liability');

        $this->createStandardLines(
            $rule,
            $freightExpenseId,
            $accruedPayablesId,
            'amount',
            [
                'shipment_id' => 'payload.shipment_id',
            ],
        );
    }

    protected function seedFuelExpenseRecordedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'fuel-expense-recorded'],
            [
                'description' => 'Fuel expense recorded (fuel expense vs AP).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $fuelExpenseId = $this->accountId('5210', 'Fuel Expense', 'expense');
        $apAccountId = $this->accountId('2100', 'Accounts Payable', 'liability');

        $this->createStandardLines(
            $rule,
            $fuelExpenseId,
            $apAccountId,
            'amount',
            [
                'vehicle_id' => 'payload.vehicle_id',
            ],
        );
    }

    protected function seedClientInvoiceIssuedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'client-invoice-issued'],
            [
                'description' => 'Client invoice issued (AR vs revenue).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $arAccountId = $this->accountId('1100', 'Accounts Receivable', 'asset');
        $revenueId = $this->accountId('4100', 'Freight Revenue', 'revenue');

        $this->createStandardLines(
            $rule,
            $arAccountId,
            $revenueId,
            'amount',
            [
                'client_id' => 'payload.client_id',
            ],
        );
    }

    protected function seedClientPaymentReceivedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'client-payment-received'],
            [
                'description' => 'Client payment received (cash vs AR).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $cashId = $this->accountId('1400', 'Cash and Bank', 'asset');
        $arAccountId = $this->accountId('1100', 'Accounts Receivable', 'asset');

        $this->createStandardLines(
            $rule,
            $cashId,
            $arAccountId,
            'amount',
            [
                'client_id' => 'payload.client_id',
            ],
        );
    }

    protected function seedClientCreditNoteRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'client-credit-note'],
            [
                'description' => 'Client credit note (revenue vs AR).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $revenueId = $this->accountId('4100', 'Freight Revenue', 'revenue');
        $arAccountId = $this->accountId('1100', 'Accounts Receivable', 'asset');

        $this->createStandardLines(
            $rule,
            $revenueId,
            $arAccountId,
            'amount',
            [
                'client_id' => 'payload.client_id',
            ],
        );
    }

    protected function seedVendorPaymentProcessedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'vendor-payment-processed'],
            [
                'description' => 'Vendor payment processed (AP vs cash).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $apAccountId = $this->accountId('2100', 'Accounts Payable', 'liability');
        $cashId = $this->accountId('1400', 'Cash and Bank', 'asset');

        $this->createStandardLines(
            $rule,
            $apAccountId,
            $cashId,
            'amount',
            [],
        );
    }

    protected function seedPurchaseOrderReceivedRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'purchase-order-received'],
            [
                'description' => 'Purchase order received (inventory/expense vs accrued payables).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $inventoryId = $this->accountId('1200', 'Inventory', 'asset');
        $accruedPayablesId = $this->accountId('2200', 'Accrued Liabilities', 'liability');

        $this->createStandardLines(
            $rule,
            $inventoryId,
            $accruedPayablesId,
            'amount',
            [],
        );
    }

    protected function seedInventoryAdjustmentRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'inventory-adjustment'],
            [
                'description' => 'Inventory adjustment (inventory adjustment expense vs inventory).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $adjustmentExpenseId = $this->accountId('5300', 'Cost of Goods Sold', 'expense');
        $inventoryId = $this->accountId('1200', 'Inventory', 'asset');

        $this->createStandardLines(
            $rule,
            $adjustmentExpenseId,
            $inventoryId,
            'amount',
            [],
        );
    }

    protected function seedAssetAcquisitionRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'asset-acquisition'],
            [
                'description' => 'Asset acquisition (fixed asset vs cash/AP).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $fixedAssetId = $this->accountId('1300', 'Fixed Assets', 'asset');
        $apAccountId = $this->accountId('2100', 'Accounts Payable', 'liability');

        $this->createStandardLines(
            $rule,
            $fixedAssetId,
            $apAccountId,
            'amount',
            [],
        );
    }

    protected function seedDepreciationPostingRule(): void
    {
        $rule = PostingRule::firstOrCreate(
            ['event_type' => 'depreciation-posting'],
            [
                'description' => 'Depreciation posting (depreciation expense vs accumulated depreciation).',
                'is_active' => true,
            ],
        );

        if ($rule->lines()->exists()) {
            return;
        }

        $deprExpenseId = $this->accountId('5400', 'Depreciation Expense', 'expense');
        $accumDeprId = $this->accountId('1320', 'Accumulated Depreciation', 'asset');

        $this->createStandardLines(
            $rule,
            $deprExpenseId,
            $accumDeprId,
            'amount',
            [],
        );
    }

    /**
     * @param  array<string, string>  $dimensionSource
     */
    protected function createStandardLines(
        PostingRule $rule,
        int $debitAccountId,
        int $creditAccountId,
        string $amountSource,
        array $dimensionSource,
    ): void {
        PostingRuleLine::create([
            'posting_rule_id' => $rule->id,
            'account_id' => $debitAccountId,
            'entry_type' => 'debit',
            'amount_source' => $amountSource,
            'dimension_source' => $dimensionSource,
            'sequence' => 1,
        ]);

        PostingRuleLine::create([
            'posting_rule_id' => $rule->id,
            'account_id' => $creditAccountId,
            'entry_type' => 'credit',
            'amount_source' => $amountSource,
            'dimension_source' => $dimensionSource,
            'sequence' => 2,
        ]);
    }

    protected function accountId(string $code, string $name, string $type): int
    {
        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'level' => 2,
                'is_posting' => true,
            ],
        )->id;
    }
}

