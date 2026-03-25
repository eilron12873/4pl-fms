<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Events\JournalPosted;
use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use App\Modules\CoreAccounting\Domain\Exceptions\JournalNotBalancedException;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;
use App\Modules\CoreAccounting\Domain\Exceptions\PostingRuleNotFoundException;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class FinancialEventController extends Controller
{
    public function __construct(
        protected FinancialEventDispatcher $dispatcher,
    ) {
    }

    public function __invoke(string $event_type, Request $request): JsonResponse
    {
        if (str_contains($event_type, '_')) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'INVALID_EVENT_TYPE',
                'message' => 'Event type must use kebab-case.',
                'event_type' => $event_type,
            ], 422);
        }

        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:255'],
            'source_system' => ['required', 'string', 'max:255'],
            'source_reference' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ]);

        $this->validateSignedPayloadIfPresent($request, $data);

        $existing = PostingSource::where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing) {
            IntegrationLog::create([
                'event_type' => $event_type,
                'idempotency_key' => $data['idempotency_key'],
                'source_system' => $data['source_system'],
                'source_reference' => $data['source_reference'],
                'status' => IntegrationLog::STATUS_DUPLICATE,
                'journal_id' => $existing->journal_id,
            ]);

            return response()->json([
                'status' => 'duplicate',
                'journal_id' => $existing->journal_id,
            ]);
        }

        // Lifecycle traceability: the event is received and will be processed next.
        $integrationLog = IntegrationLog::create([
            'event_type' => $event_type,
            'idempotency_key' => $data['idempotency_key'],
            'source_system' => $data['source_system'],
            'source_reference' => $data['source_reference'],
            'status' => IntegrationLog::STATUS_RECEIVED,
            'journal_id' => null,
            'message' => null,
        ]);

        try {
            $result = $this->dispatcher->dispatch($event_type, $data['payload'], [
                'idempotency_key' => $data['idempotency_key'],
                'source_system' => $data['source_system'],
                'source_reference' => $data['source_reference'],
            ]);
        } catch (\Throwable $e) {
            // Under concurrency, the unique constraint on posting_sources can be violated.
            // Convert that race into a deterministic duplicate outcome (not an error).
            if ($this->isPostingSourceIdempotencyUniqueViolation($e)) {
                $raceExisting = PostingSource::where('idempotency_key', $data['idempotency_key'])->first();
                if ($raceExisting) {
                    $integrationLog->update([
                        'status' => IntegrationLog::STATUS_DUPLICATE,
                        'journal_id' => $raceExisting->journal_id,
                        'message' => null,
                    ]);

                    return response()->json([
                        'status' => 'duplicate',
                        'journal_id' => $raceExisting->journal_id,
                    ]);
                }
            }

            [$httpCode, $errorCode] = $this->resolveErrorContract($e);
            $integrationLog->update([
                'status' => IntegrationLog::STATUS_ERROR,
                'message' => $e->getMessage(),
                'journal_id' => null,
            ]);

            return response()->json([
                'status' => 'error',
                'error_code' => $errorCode,
                'message' => $e->getMessage(),
                'event_type' => $event_type,
                'idempotency_key' => $data['idempotency_key'],
            ], $httpCode);
        }

        $status = $result['status'];
        $integrationLog->update([
            'status' => match ($status) {
                'posted' => IntegrationLog::STATUS_POSTED,
                'accepted' => IntegrationLog::STATUS_ACCEPTED,
                default => $status,
            },
            'journal_id' => $result['journal_id'] ?? null,
            'message' => $result['message'] ?? null,
        ]);

        if ($integrationLog->status === IntegrationLog::STATUS_POSTED && ! empty($result['journal_id'])) {
            $journal = Journal::with('lines')->find($result['journal_id']);
            if ($journal) {
                JournalPosted::dispatch($journal, [
                    'event_type' => $event_type,
                    'payload' => $data['payload'],
                    'source_system' => $data['source_system'],
                    'source_reference' => $data['source_reference'],
                ]);
            }
        }

        $code = $status === 'posted' ? 201 : 202;

        return response()->json($result, $code);
    }

    protected function isPostingSourceIdempotencyUniqueViolation(\Throwable $e): bool
    {
        if (! ($e instanceof QueryException)) {
            return false;
        }

        $msg = strtolower($e->getMessage());

        // MySQL duplicate key error: 1062, and message typically contains the index/column name.
        return str_contains($msg, 'posting_sources') && str_contains($msg, 'idempotency_key');
    }

    /**
     * Placeholder for signed payload validation per enterprise spec.
     * When X-Payload-Signature (or similar) is present, verify signature; otherwise allow.
     */
    protected function validateSignedPayloadIfPresent(Request $request, array $data): void
    {
        $signature = $request->header('X-Payload-Signature');
        if ($signature === null || $signature === '') {
            return;
        }
        // TODO: Verify $signature against payload (e.g. HMAC of json_encode($data['payload']))
        // when signing key/algorithm is defined. For now we accept any value.
    }

    /**
     * @return array{0:int,1:string}
     */
    protected function resolveErrorContract(\Throwable $e): array
    {
        return match (true) {
            $e instanceof PostingRuleNotFoundException => [422, 'RULE_NOT_FOUND'],
            $e instanceof PeriodLockedException => [409, 'PERIOD_LOCKED'],
            $e instanceof JournalNotBalancedException => [422, 'JOURNAL_NOT_BALANCED'],
            $e instanceof InvalidArgumentException => [422, 'RULE_VALIDATION_FAILED'],
            default => [500, 'INTERNAL_ERROR'],
        };
    }
}

