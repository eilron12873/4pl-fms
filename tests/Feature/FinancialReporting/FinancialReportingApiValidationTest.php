<?php

namespace Tests\Feature\FinancialReporting;

use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportingApiValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rejects_invalid_period_code(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson(route('api.financial-reporting.management-reports', [
            'period' => 'BAD-PERIOD-CODE',
        ]));

        $response->assertStatus(422);
    }

    public function test_api_rejects_invalid_date_range_order_when_period_absent(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson(route('api.financial-reporting.management-reports', [
            'from_date' => '2026-02-10',
            'to_date' => '2026-02-01',
        ]));

        $response->assertStatus(422);
    }

    public function test_api_period_override_ignores_invalid_from_to_dates(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->seed(\Database\Seeders\PeriodsSeeder::class);
        config(['gl_statements.income_statement' => []]);

        $periodCode = now()->startOfMonth()->format('Y-m');
        $period = Period::where('code', $periodCode)->firstOrFail();

        $response = $this->getJson(route('api.financial-reporting.management-reports', [
            'period' => $periodCode,
            'from_date' => '2026-12-31',
            'to_date' => '2026-01-01',
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        $data = $response->json('data');
        $this->assertSame($period->start_date->toDateString(), $data['from_date'] ?? null);
        $this->assertSame($period->end_date->toDateString(), $data['to_date'] ?? null);
    }

    public function test_api_management_pl_dimension_rejects_invalid_dimension(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson(route('api.financial-reporting.management-pl-dimension', [
            'dimension' => 'not-a-valid-dimension',
        ]));

        $response->assertStatus(422);
    }
}

