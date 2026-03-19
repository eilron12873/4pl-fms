<?php

namespace Tests\Unit\AccountsReceivable;

use App\Modules\AccountsReceivable\Application\ArReportingService;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_aging_report_groups_by_client(): void
    {
        $client = BillingClient::create([
            'code' => 'C-TEST',
            'name' => 'Test Client',
            'currency' => 'USD',
            'is_active' => true,
        ]);
        ArInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'AR-TEST-0001',
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->subDays(5),
            'status' => 'issued',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
            'amount_allocated' => 0,
            'currency' => 'USD',
        ]);

        $service = new ArReportingService();
        $rows = $service->agingReport(now()->toDateString());

        $this->assertCount(1, $rows);
        $this->assertSame($client->id, $rows[0]['client_id']);
        $this->assertEquals(100.0, $rows[0]['total']);
    }
}

