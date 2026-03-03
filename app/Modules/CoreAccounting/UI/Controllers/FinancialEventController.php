<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FinancialEventController extends Controller
{
    public function __construct(
        protected JournalService $journalService,
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

        $existing = PostingSource::where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'journal_id' => $existing->journal_id,
            ]);
        }

        // Minimal implementation: support simple shipment-delivered posting when required fields exist.
        if ($event_type === 'shipment-delivered') {
            $payload = $data['payload'];

            if (! isset($payload['amount'], $payload['revenue_account_code'], $payload['receivable_account_code'])) {
                throw ValidationException::withMessages([
                    'payload' => ['For shipment-delivered, payload.amount, payload.revenue_account_code, and payload.receivable_account_code are required.'],
                ]);
            }

            $amount = (float) $payload['amount'];

            $journal = $this->journalService->post(
                [
                    [
                        'account_code' => $payload['receivable_account_code'],
                        'debit' => $amount,
                        'credit' => 0,
                        'shipment_id' => $payload['shipment_id'] ?? null,
                        'client_id' => $payload['client_id'] ?? null,
                    ],
                    [
                        'account_code' => $payload['revenue_account_code'],
                        'debit' => 0,
                        'credit' => $amount,
                        'shipment_id' => $payload['shipment_id'] ?? null,
                        'client_id' => $payload['client_id'] ?? null,
                    ],
                ],
                [
                    'description' => $payload['description'] ?? 'Shipment delivered',
                    'journal_date' => $payload['journal_date'] ?? now()->toDateString(),
                    'source_system' => $data['source_system'],
                    'source_type' => $payload['source_type'] ?? 'shipment',
                    'source_reference' => $data['source_reference'],
                    'event_type' => $event_type,
                    'idempotency_key' => $data['idempotency_key'],
                    'payload' => $payload,
                ],
            );

            return response()->json([
                'status' => 'posted',
                'journal_id' => $journal->id,
                'journal_number' => $journal->journal_number,
            ], 201);
        }

        // For other event types, just record the posting source as unhandled for now.
        PostingSource::create([
            'journal_id' => 0,
            'source_system' => $data['source_system'],
            'source_type' => $data['payload']['source_type'] ?? null,
            'source_reference' => $data['source_reference'],
            'event_type' => $event_type,
            'idempotency_key' => $data['idempotency_key'],
            'payload' => $data['payload'],
        ]);

        return response()->json([
            'status' => 'accepted',
            'message' => 'Event recorded for future processing.',
        ], 202);
    }
}

