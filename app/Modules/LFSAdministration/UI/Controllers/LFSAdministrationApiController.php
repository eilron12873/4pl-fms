<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Core\Services\SystemSettingsService;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\LFSAdministration\Application\ActivityAuditQueryBuilder;
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
            $query->where('source_system', 'like', '%'.$data['source_system'].'%');
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

    /**
     * GET /api/lfs-administration/audit-logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $data = ActivityAuditQueryBuilder::validateFilters($request, false);
        $perPage = (int) ($data['per_page'] ?? 50);

        $query = ActivityAuditQueryBuilder::baseQuery()->with('causer');
        ActivityAuditQueryBuilder::applyFilters($query, $data);

        $paginator = $query->paginate($perPage)->withQueryString();

        $items = $paginator->getCollection()->map(function (Activity $a) {
            return [
                'id' => $a->id,
                'created_at' => $a->created_at?->toIso8601String(),
                'log_name' => $a->log_name,
                'event' => $a->event,
                'description' => $a->description,
                'subject_type' => $a->subject_type,
                'subject_id' => $a->subject_id,
                'causer_id' => $a->causer_id,
                'causer_name' => $a->causer?->name ?? $a->causer?->email,
                'properties' => $a->properties,
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
     * GET /api/lfs-administration/settings/company
     */
    public function settingsCompany(SystemSettingsService $systemSettings): JsonResponse
    {
        $g = $systemSettings->general();

        return response()->json([
            'success' => true,
            'company' => [
                'company_name' => $g->company_name,
                'company_address' => $g->company_address,
                'registration_number' => $g->registration_number,
                'telephone_number' => $g->telephone_number,
                'email_address' => $g->email_address,
                'website' => $g->website,
                'company_logo' => $g->company_logo,
                'logo_url' => $g->logo_url,
                'default_currency' => $g->default_currency,
                'default_timezone' => $g->default_timezone,
                'default_date_format' => $g->default_date_format,
                'fiscal_year_start_month' => $g->fiscal_year_start_month,
                'fiscal_year_start_day' => $g->fiscal_year_start_day,
            ],
        ]);
    }

    /**
     * GET /api/lfs-administration/settings/financial-controls
     */
    public function settingsFinancialControls(SystemSettingsService $systemSettings): JsonResponse
    {
        $f = $systemSettings->financialControls();

        return response()->json([
            'success' => true,
            'financial_controls' => [
                'max_backdating_days' => $f->max_backdating_days,
                'allow_manual_journals' => $f->allow_manual_journals,
                'thresholds' => $f->thresholds,
            ],
        ]);
    }

    /**
     * GET /api/lfs-administration/settings/tax
     */
    public function settingsTax(SystemSettingsService $systemSettings): JsonResponse
    {
        $codes = $systemSettings->taxCodesWithRates();

        $items = $codes->map(function ($tc) {
            return [
                'id' => $tc->id,
                'code' => $tc->code,
                'name' => $tc->name,
                'type' => $tc->type,
                'is_inclusive' => $tc->is_inclusive,
                'rounding_mode' => $tc->rounding_mode,
                'input_account_id' => $tc->input_account_id,
                'output_account_id' => $tc->output_account_id,
                'is_active' => $tc->is_active,
                'rates' => $tc->rates->map(fn ($r) => [
                    'id' => $r->id,
                    'rate' => (string) $r->rate,
                    'effective_from' => $r->effective_from?->toDateString(),
                    'effective_to' => $r->effective_to?->toDateString(),
                ])->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'tax_codes' => $items,
        ]);
    }
}
