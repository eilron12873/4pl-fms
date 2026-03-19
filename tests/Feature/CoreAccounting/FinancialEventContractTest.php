<?php

namespace Tests\Feature\CoreAccounting;

use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use Mockery;
use Tests\TestCase;

class FinancialEventContractTest extends TestCase
{
    public function test_event_type_must_be_kebab_case(): void
    {
        $this->withoutMiddleware();

        $this->instance(FinancialEventDispatcher::class, Mockery::mock(FinancialEventDispatcher::class));

        $response = $this->postJson('/api/financial-events/shipment_delivered', [
            'idempotency_key' => 'idem-1',
            'source_system' => 'test',
            'source_reference' => 'ref-1',
            'payload' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'INVALID_EVENT_TYPE',
            ]);
    }
}

