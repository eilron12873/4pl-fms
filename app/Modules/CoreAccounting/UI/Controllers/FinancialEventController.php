<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Events\JournalPosted;
use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialEventController extends Controller
{
    public function __construct(
        protected FinancialEventDispatcher $dispatcher,
    ) {
    }

    public function __invoke(string $event_type, Request $request): JsonResponse
    {
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

        try {
            $result = $this->dispatcher->dispatch($event_type, $data['payload'], [
                'idempotency_key' => $data['idempotency_key'],
                'source_system' => $data['source_system'],
                'source_reference' => $data['source_reference'],
            ]);
        } catch (\Throwable $e) {
            IntegrationLog::create([
                'event_type' => $event_type,
                'idempotency_key' => $data['idempotency_key'],
                'source_system' => $data['source_system'],
                'source_reference' => $data['source_reference'],
                'status' => IntegrationLog::STATUS_ERROR,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $status = $result['status'];
        IntegrationLog::create([
            'event_type' => $event_type,
            'idempotency_key' => $data['idempotency_key'],
            'source_system' => $data['source_system'],
            'source_reference' => $data['source_reference'],
            'status' => $status,
            'journal_id' => $result['journal_id'] ?? null,
            'message' => $result['message'] ?? null,
        ]);

        if ($status === 'posted' && ! empty($result['journal_id'])) {
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
}

