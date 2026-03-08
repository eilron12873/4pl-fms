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
        $query = IntegrationLog::query()->orderByDesc('created_at');

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->string('to_date'));
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('lfs-administration::integration-events', compact('logs'));
    }

    public function syncLogs(Request $request): View
    {
        $query = PostingSource::query()->with('journal')->orderByDesc('created_at');

        if ($request->filled('source_system')) {
            $query->where('source_system', 'like', '%' . $request->string('source_system') . '%');
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->string('to_date'));
        }

        $sources = $query->paginate(50)->withQueryString();

        return view('lfs-administration::sync-logs', compact('sources'));
    }
}
