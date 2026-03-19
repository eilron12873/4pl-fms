<?php

namespace Database\Seeders;

use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use Illuminate\Database\Seeder;

class TreasurySeeder extends Seeder
{
    /**
     * Reference codes used for deterministic demo seeding.
     *
     * @return array<int, string>
     */
    protected function demoReferences(): array
    {
        return [
            'INV-001',
            'PMT-AP-001',
            'WIRE-101',
            'FEE-MAR',
            'CHK-1001',
            'XFER-MAIN',
            'PR-MAR',
        ];
    }

    public function run(): void
    {
        $treasury = app(TreasuryService::class);

        $this->resetDemoData();

        $main = BankAccount::firstOrCreate(
            ['name' => 'Main Operating'],
            [
                'bank_name' => 'First National Bank',
                'account_number' => '****4521',
                'currency' => 'USD',
                'gl_account_code' => '112100',
                'opening_balance' => 50000.00,
                'opened_at' => now()->subMonths(2)->toDateString(),
                'is_active' => true,
                'notes' => null,
            ]
        );
        $main->update([
            'gl_account_code' => '112100',
            'opening_balance' => 50000.00,
        ]);

        $payroll = BankAccount::firstOrCreate(
            ['name' => 'Payroll Account'],
            [
                'bank_name' => 'First National Bank',
                'account_number' => '****7832',
                'currency' => 'USD',
                'gl_account_code' => '112200',
                'opening_balance' => 0,
                'opened_at' => now()->subMonth()->toDateString(),
                'is_active' => true,
                'notes' => null,
            ]
        );
        $payroll->update([
            'gl_account_code' => '112200',
            'opening_balance' => 0,
        ]);

        $d10 = now()->subDays(10)->toDateString();
        $d8 = now()->subDays(8)->toDateString();
        $d5 = now()->subDays(5)->toDateString();
        $d3 = now()->subDays(3)->toDateString();
        $d2 = now()->subDays(2)->toDateString();
        $d1 = now()->subDay()->toDateString();

        // Transactions (unmatched by default).
        $treasury->recordTransaction(
            bankAccountId: $main->id,
            transactionDate: $d10,
            description: 'Customer payment - ABC Corp',
            amount: 15000.00,
            type: 'deposit',
            reference: 'INV-001',
        );
        $treasury->recordTransaction(
            bankAccountId: $main->id,
            transactionDate: $d8,
            description: 'Vendor payment - Acme Freight',
            amount: -3200.50,
            type: 'withdrawal',
            reference: 'PMT-AP-001',
        );
        $treasury->recordTransaction(
            bankAccountId: $main->id,
            transactionDate: $d5,
            description: 'Deposit - wire transfer',
            amount: 25000.00,
            type: 'deposit',
            reference: 'WIRE-101',
        );
        $treasury->recordTransaction(
            bankAccountId: $main->id,
            transactionDate: $d3,
            description: 'Bank fee',
            amount: -25.00,
            type: 'fee',
            reference: 'FEE-MAR',
        );
        $treasury->recordTransaction(
            bankAccountId: $main->id,
            transactionDate: $d1,
            description: 'Office supplies',
            amount: -450.00,
            type: 'withdrawal',
            reference: 'CHK-1001',
        );

        $treasury->recordTransfer(
            fromBankAccountId: $main->id,
            toBankAccountId: $payroll->id,
            transactionDate: $d5,
            description: 'Payroll funding transfer',
            amount: 18000.00,
            reference: 'XFER-MAIN',
        );
        $treasury->recordTransaction(
            bankAccountId: $payroll->id,
            transactionDate: $d2,
            description: 'Payroll run - March',
            amount: -17500.00,
            type: 'withdrawal',
            reference: 'PR-MAR',
        );

        // Statement lines for reconciliation (unmatched).
        BankStatementLine::create([
            'bank_account_id' => $main->id,
            'statement_date' => $d10,
            'description' => 'CREDIT - Customer payment ABC Corp',
            'amount' => 15000.00,
            'reference' => 'INV-001',
            'bank_sequence' => 'STMT-001',
        ]);
        BankStatementLine::create([
            'bank_account_id' => $main->id,
            'statement_date' => $d8,
            'description' => 'DEBIT - Acme Freight Co',
            'amount' => -3200.50,
            'reference' => 'PMT-AP-001',
            'bank_sequence' => 'STMT-002',
        ]);
        BankStatementLine::create([
            'bank_account_id' => $main->id,
            'statement_date' => $d5,
            'description' => 'CREDIT - Wire transfer',
            'amount' => 25000.00,
            'reference' => 'WIRE-101',
            'bank_sequence' => 'STMT-003',
        ]);
        BankStatementLine::create([
            'bank_account_id' => $main->id,
            'statement_date' => $d3,
            'description' => 'SERVICE FEE',
            'amount' => -25.00,
            'reference' => 'FEE-MAR',
            'bank_sequence' => 'STMT-004',
        ]);
        BankStatementLine::create([
            'bank_account_id' => $main->id,
            'statement_date' => $d1,
            'description' => 'CHECK 1001',
            'amount' => -450.00,
            'reference' => 'CHK-1001',
            'bank_sequence' => 'STMT-005',
        ]);

        BankStatementLine::create([
            'bank_account_id' => $main->id,
            'statement_date' => $d5,
            'description' => 'DEBIT - Payroll funding transfer (out)',
            'amount' => -18000.00,
            'reference' => 'XFER-MAIN',
            'bank_sequence' => 'STMT-006',
        ]);
        BankStatementLine::create([
            'bank_account_id' => $payroll->id,
            'statement_date' => $d5,
            'description' => 'CREDIT - Payroll funding transfer (in)',
            'amount' => 18000.00,
            'reference' => 'XFER-MAIN',
            'bank_sequence' => 'STMT-007',
        ]);
        BankStatementLine::create([
            'bank_account_id' => $payroll->id,
            'statement_date' => $d2,
            'description' => 'DEBIT - Payroll run - March',
            'amount' => -17500.00,
            'reference' => 'PR-MAR',
            'bank_sequence' => 'STMT-008',
        ]);
    }

    protected function resetDemoData(): void
    {
        $refs = $this->demoReferences();

        $txIds = BankTransaction::query()
            ->whereIn('reference', $refs)
            ->pluck('id')
            ->all();

        if (! empty($txIds)) {
            $postingSources = PostingSource::query()
                ->where('source_system', 'treasury')
                ->whereIn('source_reference', array_map('strval', $txIds))
                ->get();

            $journalIds = $postingSources->pluck('journal_id')->unique()->all();
            if (! empty($journalIds)) {
                Journal::whereIn('id', $journalIds)->delete();
            }
        }

        BankStatementLine::query()
            ->whereIn('reference', $refs)
            ->delete();

        BankTransaction::query()
            ->whereIn('reference', $refs)
            ->delete();
    }
}
