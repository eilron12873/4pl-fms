<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed a logistics-oriented chart of accounts for LFS.
     * Account codes: 1xxx Assets, 2xxx Liabilities, 3xxx Equity, 4xxx Revenue,
     * 5xxx Cost of services, 6xxx Operating expenses, 7xxx Other income, 8xxx Other expenses.
     */
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'level' => 1, 'is_posting' => false],
            ['code' => '1010', 'name' => 'Cash on Hand', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1020', 'name' => 'Bank Account', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1030', 'name' => 'Petty Cash', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1110', 'name' => 'Trade Receivables', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '1120', 'name' => 'Unbilled Revenue', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '1130', 'name' => 'Accrued Revenue', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1210', 'name' => 'Warehouse Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '1220', 'name' => 'Packaging Materials', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            // 1300 is already used as Fixed Assets in the system; to avoid regression we keep it as-is
            ['code' => '1300', 'name' => 'Fixed Assets', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1320', 'name' => 'Accumulated Depreciation', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1350', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1400', 'name' => 'Cash and Bank', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            // Fixed assets detail (1500 block) – complements existing 1300 usage
            ['code' => '1500', 'name' => 'Fixed Assets Group', 'type' => 'asset', 'level' => 1, 'is_posting' => false],
            ['code' => '1510', 'name' => 'Trucks', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1520', 'name' => 'Trailers', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1530', 'name' => 'Forklifts', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1540', 'name' => 'Containers', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1550', 'name' => 'Warehouse Equipment', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1560', 'name' => 'IT Equipment', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            // Additional accumulated depreciation detail
            ['code' => '1610', 'name' => 'Accumulated Depreciation - Trucks', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '1620', 'name' => 'Accumulated Depreciation - Equipment', 'type' => 'asset', 'level' => 3, 'is_posting' => true],

            // Liabilities
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'level' => 1, 'is_posting' => false],
            ['code' => '2010', 'name' => 'Accounts Payable - Trade', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2020', 'name' => 'Vendor Payables', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2030', 'name' => 'Accrued Expenses', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2040', 'name' => 'Accrued Freight Cost', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2050', 'name' => 'Accrued Fuel Cost', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2060', 'name' => 'Payroll Payable', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2070', 'name' => 'Taxes Payable', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2200', 'name' => 'Accrued Liabilities', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2210', 'name' => 'Bank Loans', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2220', 'name' => 'Lease Liabilities', 'type' => 'liability', 'level' => 2, 'is_posting' => true],

            // Equity
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'level' => 1, 'is_posting' => false],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'level' => 2, 'is_posting' => true],
            ['code' => '3200', 'name' => 'Current Year Earnings', 'type' => 'equity', 'level' => 2, 'is_posting' => true],

            // Revenue
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'level' => 1, 'is_posting' => false],
            ['code' => '4100', 'name' => 'Freight Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '4110', 'name' => 'Pallet Storage Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4120', 'name' => 'Handling Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4130', 'name' => 'Pick & Pack Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4200', 'name' => 'Storage Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '4210', 'name' => 'Domestic Transport Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4220', 'name' => 'International Freight Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4230', 'name' => 'Courier Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4300', 'name' => 'Project Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '4310', 'name' => 'Project Cargo Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4320', 'name' => 'Special Handling Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4400', 'name' => 'Value Added Services Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '4410', 'name' => 'Labeling Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '4420', 'name' => 'Packaging Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],

            // Cost of services (direct logistics costs)
            ['code' => '5000', 'name' => 'Cost of Services', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '5100', 'name' => 'Storage Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '5200', 'name' => 'Transport Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '5210', 'name' => 'Fuel Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '5220', 'name' => 'Toll Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '5230', 'name' => 'Subcontracted Freight', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '5300', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '5310', 'name' => 'Packaging Materials', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '5320', 'name' => 'Handling Labor', 'type' => 'expense', 'level' => 3, 'is_posting' => true],

            // Operating expenses
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '6100', 'name' => 'Salaries and Wages', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '6110', 'name' => 'Office Salaries', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6120', 'name' => 'Management Salaries', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6200', 'name' => 'Office Expenses', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '6210', 'name' => 'Office Supplies', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6220', 'name' => 'Internet and Communication', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6300', 'name' => 'IT Expenses', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '6310', 'name' => 'Software Subscriptions', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6320', 'name' => 'System Maintenance', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6400', 'name' => 'Marketing Expenses', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '6410', 'name' => 'Advertising', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '6420', 'name' => 'Business Development', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '5400', 'name' => 'Depreciation Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],

            // Other income and expenses
            ['code' => '7000', 'name' => 'Other Income', 'type' => 'revenue', 'level' => 1, 'is_posting' => false],
            ['code' => '7010', 'name' => 'Interest Income', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '7020', 'name' => 'Miscellaneous Income', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '8000', 'name' => 'Other Expenses', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '8010', 'name' => 'Interest Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '8020', 'name' => 'Penalties and Fines', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '8030', 'name' => 'Loss on Asset Disposal', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
        ];

        $parentIds = [];
        foreach ($accounts as $row) {
            $parentCode = $row['code'][0] . str_repeat('0', strlen($row['code']) - 1);
            $parentId = ($parentCode !== $row['code']) ? ($parentIds[$parentCode] ?? null) : null;

            $account = Account::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'parent_id' => $parentId,
                    'level' => $row['level'],
                    'is_posting' => $row['is_posting'],
                ],
            );
            $parentIds[$row['code']] = $account->id;
        }
    }
}
