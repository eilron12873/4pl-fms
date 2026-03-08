<?php

namespace Database\Seeders;

use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use Illuminate\Database\Seeder;

class TreasurySeeder extends Seeder
{
    public function run(): void
    {
        $treasury = app(TreasuryService::class);

        $main = BankAccount::firstOrCreate(
            ['name' => 'Main Operating'],
            [
                'bank_name' => 'First National Bank',
                'account_number' => '****4521',
                'currency' => 'USD',
                'gl_account_code' => '1400',
                'opening_balance' => 50000.00,
                'opened_at' => now()->subMonths(2)->toDateString(),
                'is_active' => true,
            ]
        );

        $payroll = BankAccount::firstOrCreate(
            ['name' => 'Payroll Account'],
            [
                'bank_name' => 'First National Bank',
                'account_number' => '****7832',
                'currency' => 'USD',
                'gl_account_code' => '1400',
                'opening_balance' => 0,
                'opened_at' => now()->subMonth()->toDateString(),
                'is_active' => true,
            ]
        );

        if ($main->transactions()->count() === 0) {
            $treasury->recordTransaction($main->id, now()->subDays(10)->toDateString(), 'Customer payment - ABC Corp', 15000.00, 'deposit', 'INV-001');
            $treasury->recordTransaction($main->id, now()->subDays(8)->toDateString(), 'Vendor payment - Acme Freight', -3200.50, 'withdrawal', 'PMT-AP-001');
            $treasury->recordTransaction($main->id, now()->subDays(5)->toDateString(), 'Deposit - wire transfer', 25000.00, 'deposit', 'WIRE-101');
            $treasury->recordTransaction($main->id, now()->subDays(3)->toDateString(), 'Bank fee', -25.00, 'fee', 'FEE-MAR');
            $treasury->recordTransaction($main->id, now()->subDay()->toDateString(), 'Office supplies', -450.00, 'withdrawal', 'CHK-1001');
        }

        if ($payroll->transactions()->count() === 0) {
            $treasury->recordTransaction($payroll->id, now()->subDays(5)->toDateString(), 'Payroll funding transfer', 18000.00, 'transfer', 'XFER-MAIN');
            $treasury->recordTransaction($payroll->id, now()->subDays(2)->toDateString(), 'Payroll run - March', -17500.00, 'withdrawal', 'PR-MAR');
        }

        if (BankStatementLine::where('bank_account_id', $main->id)->count() === 0) {
            BankStatementLine::create([
                'bank_account_id' => $main->id,
                'statement_date' => now()->subDays(10)->toDateString(),
                'description' => 'CREDIT - Customer payment ABC Corp',
                'amount' => 15000.00,
                'reference' => 'INV-001',
                'bank_sequence' => 'STMT-001',
            ]);
            BankStatementLine::create([
                'bank_account_id' => $main->id,
                'statement_date' => now()->subDays(8)->toDateString(),
                'description' => 'DEBIT - Acme Freight Co',
                'amount' => -3200.50,
                'reference' => 'PMT-AP-001',
                'bank_sequence' => 'STMT-002',
            ]);
            BankStatementLine::create([
                'bank_account_id' => $main->id,
                'statement_date' => now()->subDays(5)->toDateString(),
                'description' => 'CREDIT - Wire transfer',
                'amount' => 25000.00,
                'reference' => 'WIRE-101',
                'bank_sequence' => 'STMT-003',
            ]);
            BankStatementLine::create([
                'bank_account_id' => $main->id,
                'statement_date' => now()->subDays(3)->toDateString(),
                'description' => 'SERVICE FEE',
                'amount' => -25.00,
                'reference' => 'FEE-MAR',
                'bank_sequence' => 'STMT-004',
            ]);
            BankStatementLine::create([
                'bank_account_id' => $main->id,
                'statement_date' => now()->subDay()->toDateString(),
                'description' => 'CHECK 1001',
                'amount' => -450.00,
                'reference' => 'CHK-1001',
                'bank_sequence' => 'STMT-005',
            ]);
        }
    }
}
