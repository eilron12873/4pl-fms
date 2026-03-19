<?php

namespace Tests\Feature\AccountsReceivable;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_issue_manual_invoice(): void
    {
        $user = User::factory()->create();
        // In tests we bypass permission checks by assigning a role with all permissions if available,
        // or by disabling middleware in the test environment if needed.
        $this->withoutMiddleware();
        $this->actingAs($user);

        $client = BillingClient::create([
            'code' => 'C-TEST',
            'name' => 'Test Client',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $response = $this->post(route('accounts-receivable.invoices.store'), [
            'client_id' => $client->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => $client->currency,
            'lines' => [
                ['description' => 'Test line', 'amount' => 100],
            ],
        ]);

        $response->assertRedirect();

        $invoice = ArInvoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame('draft', $invoice->status);

        $issueResponse = $this->post(route('accounts-receivable.invoices.issue', $invoice->id));
        $issueResponse->assertRedirect();

        $invoice->refresh();
        $this->assertSame('issued', $invoice->status);
    }
}

