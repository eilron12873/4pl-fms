<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed a minimal logistics-oriented chart of accounts for LFS.
     * Account codes: 1xxx Assets, 2xxx Liabilities, 3xxx Equity, 4xxx Revenue, 5xxx Expense.
     */
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'level' => 1, 'is_posting' => false],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1300', 'name' => 'Fixed Assets', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1320', 'name' => 'Accumulated Depreciation', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '1400', 'name' => 'Cash and Bank', 'type' => 'asset', 'level' => 2, 'is_posting' => true],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'level' => 1, 'is_posting' => false],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '2200', 'name' => 'Accrued Liabilities', 'type' => 'liability', 'level' => 2, 'is_posting' => true],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'level' => 1, 'is_posting' => false],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'level' => 2, 'is_posting' => true],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'level' => 1, 'is_posting' => false],
            ['code' => '4100', 'name' => 'Freight Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '4200', 'name' => 'Storage Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '4300', 'name' => 'Project Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '5100', 'name' => 'Storage Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '5200', 'name' => 'Transport Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '5300', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '5400', 'name' => 'Depreciation Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
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
