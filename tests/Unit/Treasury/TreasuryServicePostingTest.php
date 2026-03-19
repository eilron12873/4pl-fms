<?php

namespace Tests\Unit\Treasury;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasuryServicePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCoreForPosting(): void
    {
        // Ensure Accounts exist (JournalService will also firstOrCreate, but we want deterministic account types).
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $this->seed(\Database\Seeders\PeriodsSeeder::class);
    }

    public function test_record_transaction_is_idempotent_for_same_input(): void
    {
        $this->seedCoreForPosting();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $bank = BankAccount::create([
            'name' => 'TB-Deposit',
            'bank_name' => 'Test Bank',
            'account_number' => '0001',
            'currency' => 'USD',
            'gl_account_code' => '112100',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        /** @var TreasuryService $service */
        $service = app(TreasuryService::class);

        $transactionDate = now()->toDateString();
        $tx1 = $service->recordTransaction(
            bankAccountId: $bank->id,
            transactionDate: $transactionDate,
            description: 'DEP-TEST',
            amount: 10.25,
            type: 'deposit',
            reference: 'REF-DEP-1',
            sourceType: null,
            sourceId: null,
            counterpartyGlAccountCode: null,
        );

        $journalCountAfterFirst = Journal::count();
        $postingSourceCountAfterFirst = PostingSource::count();
        $bankTxCountAfterFirst = BankTransaction::count();

        $tx2 = $service->recordTransaction(
            bankAccountId: $bank->id,
            transactionDate: $transactionDate,
            description: 'DEP-TEST',
            amount: 10.25,
            type: 'deposit',
            reference: 'REF-DEP-1',
            sourceType: null,
            sourceId: null,
            counterpartyGlAccountCode: null,
        );

        $this->assertSame($tx1->id, $tx2->id);
        $this->assertSame($journalCountAfterFirst, Journal::count());
        $this->assertSame($postingSourceCountAfterFirst, PostingSource::count());
        $this->assertSame($bankTxCountAfterFirst, BankTransaction::count());
    }

    public function test_record_transfer_posts_only_once_and_creates_both_legs(): void
    {
        $this->seedCoreForPosting();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $from = BankAccount::create([
            'name' => 'TB-From',
            'bank_name' => 'Test Bank',
            'account_number' => '1000',
            'currency' => 'USD',
            'gl_account_code' => '112100',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        $to = BankAccount::create([
            'name' => 'TB-To',
            'bank_name' => 'Test Bank',
            'account_number' => '2000',
            'currency' => 'USD',
            'gl_account_code' => '112200',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        $service = app(TreasuryService::class);

        $transactionDate = now()->toDateString();

        $legs1 = $service->recordTransfer(
            fromBankAccountId: $from->id,
            toBankAccountId: $to->id,
            transactionDate: $transactionDate,
            description: 'TRF-TEST',
            amount: 50.00,
            reference: 'REF-TRF-1',
        );

        $journalCountAfterFirst = Journal::count();
        $bankTxCountAfterFirst = BankTransaction::count();

        $legs2 = $service->recordTransfer(
            fromBankAccountId: $from->id,
            toBankAccountId: $to->id,
            transactionDate: $transactionDate,
            description: 'TRF-TEST',
            amount: 50.00,
            reference: 'REF-TRF-1',
        );

        $this->assertSame($legs1['out']->id, $legs2['out']->id);
        $this->assertSame($legs1['in']->id, $legs2['in']->id);

        $this->assertSame($journalCountAfterFirst, Journal::count());
        $this->assertSame($bankTxCountAfterFirst, BankTransaction::count());
    }

    public function test_reconciliation_match_is_idempotent_and_unmatch_clears_both_sides(): void
    {
        // Journals not needed here; but the service requires dependencies.
        $this->seedCoreForPosting();

        $bank = BankAccount::create([
            'name' => 'TB-Recon',
            'bank_name' => 'Test Bank',
            'account_number' => '3000',
            'currency' => 'USD',
            'gl_account_code' => '112100',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        $tx = BankTransaction::create([
            'bank_account_id' => $bank->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'TX-RECON',
            'amount' => 25.00,
            'reference' => 'REF-TX-RECON',
            'type' => 'deposit',
            'source_type' => null,
            'source_id' => null,
            'reconciled_at' => null,
            'idempotency_key' => 'test-tx-recon-' . bin2hex(random_bytes(4)),
        ]);

        $statementLine = BankStatementLine::create([
            'bank_account_id' => $bank->id,
            'statement_date' => now()->toDateString(),
            'description' => 'STMT',
            'amount' => 25.00,
            'reference' => 'REF-STMT-RECON',
            'bank_sequence' => 'SEQ-1',
            'bank_transaction_id' => null,
            'matched_at' => null,
        ]);

        $service = app(TreasuryService::class);

        $service->matchStatementLineToTransaction($statementLine, $tx);

        $statementLine->refresh();
        $tx->refresh();

        $this->assertTrue($statementLine->isMatched());
        $this->assertSame((int) $statementLine->bank_transaction_id, (int) $tx->id);
        $this->assertNotNull($tx->reconciled_at);

        // Idempotent re-match
        $service->matchStatementLineToTransaction($statementLine, $tx);
        $statementLine->refresh();
        $tx->refresh();
        $this->assertNotNull($tx->reconciled_at);

        // Unmatch clears both
        $service->unmatchStatementLine($statementLine);
        $statementLine->refresh();
        $tx->refresh();

        $this->assertFalse($statementLine->isMatched());
        $this->assertNull($tx->reconciled_at);
    }

    public function test_reconciliation_match_rejects_amount_mismatch(): void
    {
        $this->seedCoreForPosting();

        $bank = BankAccount::create([
            'name' => 'TB-ReconMismatch',
            'bank_name' => 'Test Bank',
            'account_number' => '3500',
            'currency' => 'USD',
            'gl_account_code' => '112100',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        $tx = BankTransaction::create([
            'bank_account_id' => $bank->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'TX-RECON-MISMATCH',
            'amount' => 25.00,
            'reference' => 'REF-TX-RECON-MISMATCH',
            'type' => 'deposit',
            'source_type' => null,
            'source_id' => null,
            'reconciled_at' => null,
            'idempotency_key' => 'test-tx-recon-mismatch-' . bin2hex(random_bytes(4)),
        ]);

        $statementLine = BankStatementLine::create([
            'bank_account_id' => $bank->id,
            'statement_date' => now()->toDateString(),
            'description' => 'STMT',
            'amount' => 24.99, // mismatch by 0.01
            'reference' => 'REF-STMT-RECON-MISMATCH',
            'bank_sequence' => 'SEQ-1',
            'bank_transaction_id' => null,
            'matched_at' => null,
        ]);

        $service = app(TreasuryService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statement line amount does not match transaction amount.');

        $service->matchStatementLineToTransaction($statementLine, $tx);
    }
}

