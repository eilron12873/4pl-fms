<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LFSAdministrationApiController extends Controller
{
    /**
     * GET /api/lfs-administration/integration-events
     */
    public function integrationEvents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'in:received,posted,accepted,duplicate,error'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (
            ! empty($data['from_date'])
            && ! empty($data['to_date'])
            && \Carbon\Carbon::parse($data['from_date'])->gt(\Carbon\Carbon::parse($data['to_date']))
        ) {
            throw ValidationException::withMessages([
                'to_date' => ['The to_date must be greater than or equal to from_date.'],
            ]);
        }

        $perPage = (int) ($data['per_page'] ?? 50);

        $query = IntegrationLog::query()
            ->select([
                'id',
                'created_at',
                'event_type',
                'idempotency_key',
                'source_system',
                'source_reference',
                'status',
                'message',
                'journal_id',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($data['event_type'])) {
            $query->where('event_type', $data['event_type']);
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['from_date'])) {
            $query->whereDate('created_at', '>=', $data['from_date']);
        }
        if (! empty($data['to_date'])) {
            $query->whereDate('created_at', '<=', $data['to_date']);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $items = $paginator->getCollection()->map(function (IntegrationLog $log) {
            return [
                'id' => $log->id,
                'created_at' => $log->created_at?->toIso8601String(),
                'event_type' => $log->event_type,
                'source_system' => $log->source_system,
                'source_reference' => $log->source_reference,
                'status' => $log->status,
                'idempotency_key' => $log->idempotency_key,
                'journal_id' => $log->journal_id,
                'message' => $log->message,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/lfs-administration/sync-logs
     */
    public function syncLogs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_system' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (
            ! empty($data['from_date'])
            && ! empty($data['to_date'])
            && \Carbon\Carbon::parse($data['from_date'])->gt(\Carbon\Carbon::parse($data['to_date']))
        ) {
            throw ValidationException::withMessages([
                'to_date' => ['The to_date must be greater than or equal to from_date.'],
            ]);
        }

        $perPage = (int) ($data['per_page'] ?? 50);

        $query = PostingSource::query()
            ->with('journal')
            ->select([
                'id',
                'created_at',
                'source_system',
                'source_reference',
                'event_type',
                'idempotency_key',
                'journal_id',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($data['source_system'])) {
            $query->where('source_system', 'like', '%' . $data['source_system'] . '%');
        }
        if (! empty($data['event_type'])) {
            $query->where('event_type', $data['event_type']);
        }
        if (! empty($data['from_date'])) {
            $query->whereDate('created_at', '>=', $data['from_date']);
        }
        if (! empty($data['to_date'])) {
            $query->whereDate('created_at', '<=', $data['to_date']);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $items = $paginator->getCollection()->map(function (PostingSource $src) {
            return [
                'id' => $src->id,
                'created_at' => $src->created_at?->toIso8601String(),
                'event_type' => $src->event_type,
                'source_system' => $src->source_system,
                'source_reference' => $src->source_reference,
                'idempotency_key' => $src->idempotency_key,
                'journal_id' => $src->journal_id,
                'journal_number' => $src->journal?->journal_number,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

