<?php

namespace Tests\Feature\Treasury;

use App\Modules\Treasury\Application\TreasuryService;
use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use App\Modules\Treasury\Infrastructure\Models\BankStatementLine;
use App\Modules\Treasury\Infrastructure\Models\BankTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasuryApiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCoreForPosting(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $this->seed(\Database\Seeders\PeriodsSeeder::class);
    }

    public function test_api_rejects_negative_deposit_amount(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->seedCoreForPosting();

        $bank = BankAccount::create([
            'name' => 'TB-API-DEP',
            'bank_name' => 'Test Bank',
            'account_number' => '4001',
            'currency' => 'USD',
            'gl_account_code' => '112100',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        $response = $this->postJson(route('api.treasury.transactions.store'), [
            'bank_account_id' => $bank->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'NEG-DEP',
            'amount' => -10.00,
            'type' => 'deposit',
            'reference' => 'API-NEG-DEP',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'success' => false,
        ]);
    }

    public function test_api_transfer_requires_destination_bank_account(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->seedCoreForPosting();

        $from = BankAccount::create([
            'name' => 'TB-API-XFER-FROM',
            'bank_name' => 'Test Bank',
            'account_number' => '4101',
            'currency' => 'USD',
            'gl_account_code' => '112100',
            'opening_balance' => 0,
            'opened_at' => now()->toDateString(),
            'is_active' => true,
            'notes' => null,
        ]);

        $response = $this->postJson(route('api.treasury.transactions.store'), [
            'bank_account_id' => $from->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'XFER',
            'amount' => 100.00,
            'type' => 'transfer',
            'reference' => 'API-XFER-MISSING-DEST',
            // destination_bank_account_id omitted intentionally
        ]);

        $response->assertStatus(422);
    }

    public function test_api_reconciliation_match_rejects_amount_mismatch(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // No GL posting needed, but TreasuryService is constructed with JournalService.
        $this->seedCoreForPosting();

        $bank = BankAccount::create([
            'name' => 'TB-API-RECON-MISMATCH',
            'bank_name' => 'Test Bank',
            'account_number' => '4201',
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
            'reference' => 'API-TX-RECON-MISMATCH',
            'type' => 'deposit',
            'source_type' => null,
            'source_id' => null,
            'reconciled_at' => null,
            'idempotency_key' => 'api-tx-recon-' . bin2hex(random_bytes(4)),
        ]);

        $statementLine = BankStatementLine::create([
            'bank_account_id' => $bank->id,
            'statement_date' => now()->toDateString(),
            'description' => 'STMT',
            'amount' => 24.99,
            'reference' => 'API-STMT-RECON-MISMATCH',
            'bank_sequence' => 'SEQ-1',
            'bank_transaction_id' => null,
            'matched_at' => null,
        ]);

        $response = $this->postJson(route('api.treasury.reconciliation.match'), [
            'statement_line_id' => $statementLine->id,
            'bank_transaction_id' => $tx->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'success' => false,
        ]);
    }
}

