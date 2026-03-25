<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\IntegrationLog;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

class LFSAdministrationController extends Controller
{
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
        $query = Activity::query()->with('causer')->orderByDesc('created_at');

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name'));
        }
        if ($request->filled('event')) {
            $query->where('event', 'like', '%' . $request->string('event') . '%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->string('to_date'));
        }

        $activities = $query->paginate(50)->withQueryString();

        return view('lfs-administration::audit-logs', compact('activities'));
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

        return view('lfs-administration::roles.edit', compact('role', 'permissions', 'rolePermissionIds'));
    }

    public function roleUpdate(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('lfs-administration.manage');
        $role = Role::where('guard_name', 'web')->findOrFail($id);
        $permissionIds = $request->input('permissions', []);
        $permissions = Permission::whereIn('id', $permissionIds)->where('guard_name', 'web')->pluck('name');
        $role->syncPermissions($permissions);

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

        $sources = $query->paginate(50)->withQueryString();

        return view('lfs-administration::sync-logs', compact('sources'));
    }
}
