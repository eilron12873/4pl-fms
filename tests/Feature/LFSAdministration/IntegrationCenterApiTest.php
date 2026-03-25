<?php

namespace Tests\Feature\LFSAdministration;

use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Models\IntegrationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class IntegrationCenterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_integration_events_rejects_invalid_status(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $url = route('api.lfs-administration.integration-events') . '?status=' . urlencode('not-a-valid-status');
        $response = $this->getJson($url);

        $response->assertStatus(422);
    }

    public function test_api_integration_events_rejects_invalid_date_range_order(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $url = route('api.lfs-administration.integration-events')
            . '?from_date=' . urlencode('2026-02-10')
            . '&to_date=' . urlencode('2026-02-01');
        $response = $this->getJson($url);

        $response->assertStatus(422);
    }

    public function test_api_integration_events_applies_deterministic_ordering_created_at_id(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $t = Carbon::now()->subDay();

        $log1 = IntegrationLog::create([
            'event_type' => 'e-1',
            'idempotency_key' => 'idem-ic-api-1',
            'source_system' => 'wms',
            'source_reference' => 'ref-1',
            'status' => IntegrationLog::STATUS_ACCEPTED,
            'message' => 'm1',
            'journal_id' => null,
        ]);
        $log1->created_at = $t;
        $log1->updated_at = $t;
        $log1->save();

        $log2 = IntegrationLog::create([
            'event_type' => 'e-2',
            'idempotency_key' => 'idem-ic-api-2',
            'source_system' => 'wms',
            'source_reference' => 'ref-2',
            'status' => IntegrationLog::STATUS_POSTED,
            'message' => 'm2',
            'journal_id' => null,
        ]);
        $log2->created_at = $t;
        $log2->updated_at = $t;
        $log2->save();

        $url = route('api.lfs-administration.integration-events') . '?per_page=10';
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $items = $response->json('items');

        // Same created_at: ordering must be by id desc tie-breaker.
        $this->assertSame($log2->id, $items[0]['id']);
    }

    public function test_api_sync_logs_applies_deterministic_ordering_created_at_id(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $t = Carbon::now()->subDay();

        $src1 = PostingSource::create([
            'journal_id' => null,
            'source_system' => 'wms',
            'source_type' => null,
            'source_reference' => 'ref-src-1',
            'event_type' => 'e-1',
            'idempotency_key' => 'idem-src-1',
            'payload' => [],
        ]);
        $src1->created_at = $t;
        $src1->updated_at = $t;
        $src1->save();

        $src2 = PostingSource::create([
            'journal_id' => null,
            'source_system' => 'wms',
            'source_type' => null,
            'source_reference' => 'ref-src-2',
            'event_type' => 'e-2',
            'idempotency_key' => 'idem-src-2',
            'payload' => [],
        ]);
        $src2->created_at = $t;
        $src2->updated_at = $t;
        $src2->save();

        $url = route('api.lfs-administration.sync-logs') . '?per_page=10';
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $items = $response->json('items');

        $this->assertSame($src2->id, $items[0]['id']);
    }
}

