<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\LFSAdministration\Application\ActivityAuditQueryBuilder;
use App\Modules\LFSAdministration\Application\AuditSubjectLinkResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LFSAdministrationController extends Controller
{
    private const RBAC_AUDIT_PERMISSION_SLICE = 150;

    public function __construct(
        protected AuditService $audit,
        protected AuditSubjectLinkResolver $subjectLinkResolver,
    ) {}

    public function index(): View
    {
        $recentFinancial = Activity::where('log_name', AuditService::LOG_FINANCIAL)
            ->with('causer')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $appEnv = config('app.env');
        $laravelVersion = \Illuminate\Foundation\Application::VERSION;

        return view('lfs-administration::index', [
            'recentFinancial' => $recentFinancial,
            'appEnv' => $appEnv,
            'laravelVersion' => $laravelVersion,
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $filters = ActivityAuditQueryBuilder::validateFilters($request, false);
        $query = ActivityAuditQueryBuilder::baseQuery()->with('causer');
        ActivityAuditQueryBuilder::applyFilters($query, $filters);

        $activities = $query->paginate(50)->withQueryString();

        return view('lfs-administration::audit-logs', compact('activities'));
    }

    public function auditLogShow(Activity $activity): View
    {
        $activity->load(['causer', 'subject']);

        $subjectUrl = $this->subjectLinkResolver->resolveUrl($activity->subject_type, $activity->subject_id);

        return view('lfs-administration::audit-log-show', [
            'activity' => $activity,
            'subjectUrl' => $subjectUrl,
        ]);
    }

    public function auditLogsExport(Request $request): StreamedResponse
    {
        $filters = ActivityAuditQueryBuilder::validateFilters($request, true);
        $query = ActivityAuditQueryBuilder::baseQuery();
        ActivityAuditQueryBuilder::applyFilters($query, $filters);

        $maxRows = (int) config('audit.export.max_rows', 10_000);
        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $maxRows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'id',
                'created_at',
                'log_name',
                'event',
                'description',
                'causer_type',
                'causer_id',
                'subject_type',
                'subject_id',
                'properties_json',
            ]);

            $count = 0;
            foreach ($query->clone()->cursor() as $activity) {
                if (++$count > $maxRows) {
                    break;
                }
                /** @var Activity $activity */
                $props = $activity->properties;
                fputcsv($handle, [
                    $activity->id,
                    $activity->created_at?->toIso8601String(),
                    $activity->log_name,
                    $activity->event,
                    $activity->description,
                    $activity->causer_type,
                    $activity->causer_id,
                    $activity->subject_type,
                    $activity->subject_id,
                    is_array($props) ? json_encode($props, JSON_UNESCAPED_UNICODE) : '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function roles(): View
    {
        $roles = Role::where('guard_name', 'web')->withCount('permissions')->orderBy('name')->get();
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();

        return view('lfs-administration::roles.index', compact('roles', 'permissions'));
    }

    public function roleEdit(int $id): View
    {
        $role = Role::where('guard_name', 'web')->with('permissions')->findOrFail($id);
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();
        $rolePermissionIds = $role->permissions->pluck('id')->all();
        $permissionsByGroup = $this->groupPermissionsForDisplay($permissions);

        return view('lfs-administration::roles.edit', compact('role', 'rolePermissionIds', 'permissionsByGroup'));
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return Collection<string, Collection<int, Permission>>
     */
    private function groupPermissionsForDisplay(Collection $permissions): Collection
    {
        $grouped = $permissions->groupBy(function (Permission $p): string {
            if (! str_contains($p->name, '.')) {
                return (string) __('Other');
            }

            return explode('.', $p->name, 2)[0];
        });

        return $grouped->sortKeys();
    }

    public function roleUpdate(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('lfs-administration.manage');
        $role = Role::where('guard_name', 'web')->findOrFail($id);

        $before = $role->getPermissionNames()->sort()->values()->all();

        $permissionIds = $request->input('permissions', []);
        $permissions = Permission::whereIn('id', $permissionIds)->where('guard_name', 'web')->pluck('name');
        $role->syncPermissions($permissions);

        $role->refresh();
        $after = $role->getPermissionNames()->sort()->values()->all();

        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        $addedSlice = array_slice($added, 0, self::RBAC_AUDIT_PERMISSION_SLICE);
        $removedSlice = array_slice($removed, 0, self::RBAC_AUDIT_PERMISSION_SLICE);

        $this->audit->log(
            description: __('Role permissions updated: :name', ['name' => $role->name]),
            event: 'role.permissions_updated',
            subject: $role,
            properties: [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_added' => $addedSlice,
                'permissions_added_count' => count($added),
                'permissions_added_truncated' => count($added) > self::RBAC_AUDIT_PERMISSION_SLICE,
                'permissions_removed' => $removedSlice,
                'permissions_removed_count' => count($removed),
                'permissions_removed_truncated' => count($removed) > self::RBAC_AUDIT_PERMISSION_SLICE,
                'permissions_count_after' => count($after),
            ],
            logName: AuditService::LOG_SECURITY,
        );

        return redirect()->route('lfs-administration.roles')->with('success', __('Role permissions updated.'));
    }

    public function integrationEvents(Request $request): View
    {
        $data = $request->validate([
            'event_type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'in:received,posted,accepted,duplicate,error'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
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

        $query = IntegrationLog::query()
            ->select([
                'id',
                'created_at',
                'event_type',
                'source_system',
                'source_reference',
                'status',
                'message',
                'journal_id',
            ])
            ->orderByDesc('created_at');

        // Deterministic ordering: tie-break on id for stable pagination.
        $query->orderByDesc('id');

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

        $logs = $query->paginate(50)->withQueryString();

        return view('lfs-administration::integration-events', compact('logs'));
    }

    public function syncLogs(Request $request): View
    {
        $data = $request->validate([
            'source_system' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
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
            // Deterministic ordering: tie-break on id for stable pagination.
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

        $sources = $query->paginate(50)->withQueryString();

        return view('lfs-administration::sync-logs', compact('sources'));
    }
}
