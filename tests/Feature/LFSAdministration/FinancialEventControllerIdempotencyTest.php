<?php

namespace Tests\Feature\LFSAdministration;

use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Models\IntegrationLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEventControllerIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_received_is_written_before_dispatch_and_transitions_to_accepted(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $idempotencyKey = 'idem-fin-1';

        $this->mock(FinancialEventDispatcher::class, function ($mock) use ($idempotencyKey) {
            $mock->shouldReceive('dispatch')
                ->once()
                ->andReturnUsing(function (string $eventType, array $payload, array $context) use ($idempotencyKey) {
                    $log = IntegrationLog::where('idempotency_key', $idempotencyKey)->firstOrFail();
                    $this->assertSame(IntegrationLog::STATUS_RECEIVED, $log->status);

                    return [
                        'status' => 'accepted',
                        'message' => 'Event recorded for future processing.',
                    ];
                });
        });

        $response = $this->postJson(route('api.financial-events.handle', [
            'event_type' => 'unsupported-event',
        ]), [
            'idempotency_key' => $idempotencyKey,
            'source_system' => 'wms',
            'source_reference' => 'ref-1',
            'payload' => ['k' => 'v'],
        ]);

        $response->assertStatus(202);
        $response->assertJsonFragment(['status' => 'accepted']);

        $log = IntegrationLog::where('idempotency_key', $idempotencyKey)->firstOrFail();
        $this->assertSame(IntegrationLog::STATUS_ACCEPTED, $log->status);
        $this->assertNotNull($log->message);
    }

    public function test_duplicate_idempotency_key_returns_duplicate_when_posting_source_exists(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $idempotencyKey = 'idem-fin-2';

        $journal = Journal::create([
            'journal_number' => 'J-DUP-1',
            'journal_date' => now()->toDateString(),
            'period' => now()->format('Y-m'),
            'description' => 'dup test',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        PostingSource::create([
            'journal_id' => $journal->id,
            'source_system' => 'wms',
            'source_type' => null,
            'source_reference' => 'ref-dup',
            'event_type' => 'whatever',
            'idempotency_key' => $idempotencyKey,
            'payload' => [],
        ]);

        $response = $this->postJson(route('api.financial-events.handle', [
            'event_type' => 'unsupported-event',
        ]), [
            'idempotency_key' => $idempotencyKey,
            'source_system' => 'wms',
            'source_reference' => 'ref-dup',
            'payload' => ['k' => 'v'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'duplicate',
            'journal_id' => $journal->id,
        ]);

        $log = IntegrationLog::where('idempotency_key', $idempotencyKey)->firstOrFail();
        $this->assertSame(IntegrationLog::STATUS_DUPLICATE, $log->status);
        $this->assertSame($journal->id, $log->journal_id);
    }

    public function test_unique_idempotency_race_violation_converts_to_duplicate(): void
    {
        $this->withoutMiddleware();
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $idempotencyKey = 'idem-fin-3';

        $this->mock(FinancialEventDispatcher::class, function ($mock) use ($idempotencyKey) {
            $mock->shouldReceive('dispatch')
                ->once()
                ->andReturnUsing(function (string $eventType, array $payload, array $context) use ($idempotencyKey) {
                    // Simulate the concurrent request creating the PostingSource first.
                    PostingSource::create([
                        'journal_id' => null,
                        'source_system' => $context['source_system'],
                        'source_type' => null,
                        'source_reference' => $context['source_reference'],
                        'event_type' => $eventType,
                        'idempotency_key' => $idempotencyKey,
                        'payload' => [],
                    ]);

                    throw new QueryException(
                        'mysql',
                        '',
                        [],
                        new \Exception('Duplicate entry posting_sources for idempotency_key')
                    );
                });
        });

        $response = $this->postJson(route('api.financial-events.handle', [
            'event_type' => 'unsupported-event',
        ]), [
            'idempotency_key' => $idempotencyKey,
            'source_system' => 'wms',
            'source_reference' => 'ref-3',
            'payload' => ['k' => 'v'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'duplicate']);

        $log = IntegrationLog::where('idempotency_key', $idempotencyKey)->firstOrFail();
        $this->assertSame(IntegrationLog::STATUS_DUPLICATE, $log->status);
        $this->assertNull($log->journal_id);
    }
}

