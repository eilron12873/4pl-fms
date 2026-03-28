<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UsersManagementController extends Controller
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->value();

        $query = User::query()->with('roles');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('department', 'like', $like)
                    ->orWhere('position', 'like', $like);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $stats = [
            'super_admin' => User::role(UserPolicy::ROLE_SUPER_ADMIN)->count(),
            'admin' => User::role(UserPolicy::ROLE_ADMIN)->count(),
            'manager_supervisor_finance' => User::query()
                ->whereHas('roles', function ($q): void {
                    $q->whereIn('name', ['Manager', 'Supervisor', 'Accountant']);
                })
                ->count(),
            'other' => User::whereDoesntHave('roles', function ($q): void {
                $q->whereIn('name', [
                    UserPolicy::ROLE_SUPER_ADMIN,
                    UserPolicy::ROLE_ADMIN,
                    'Manager',
                    'Supervisor',
                    'Accountant',
                ]);
            })->count(),
        ];

        return view('lfs-administration::settings.users.index', compact('users', 'stats', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $assignableRoles = $this->assignableRoles(Auth::user());

        return view('lfs-administration::settings.users.create', compact('assignableRoles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $assignable = $this->assignableRoleNames(Auth::user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in($assignable)],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        abort_unless(app(UserPolicy::class)->assignRoleName(Auth::user(), $validated['role']), 403);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->syncRoles([$validated['role']]);
        $this->forgetPermissionCache();
        $user->refresh();
        $user->load('roles');

        $this->audit->log(
            description: __('User :email created', ['email' => $user->email]),
            event: 'users.created',
            subject: $user,
            properties: [
                'group' => 'users',
                'after' => $this->userAuditSnapshot($user),
            ],
            logName: AuditService::LOG_SECURITY,
        );

        return redirect()->route('lfs-administration.settings.users')->with('success', __('User created.'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $assignableRoles = $this->assignableRoles(Auth::user());
        $user->load('roles');
        $currentRoleName = $user->roles->first()?->name;

        return view('lfs-administration::settings.users.edit', compact('user', 'assignableRoles', 'currentRoleName'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $assignable = $this->assignableRoleNames(Auth::user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in($assignable)],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        abort_unless(app(UserPolicy::class)->assignRoleName(Auth::user(), $validated['role']), 403);

        if (UserPolicy::wouldRemoveLastSuperAdmin($user, $validated['role'])) {
            return redirect()->back()->withInput()->with('error', __('Cannot remove the last Super Admin.'));
        }

        if ($user->id === Auth::id() && ! $request->boolean('is_active', true)) {
            return redirect()->back()->withInput()->with('error', __('You cannot deactivate your own account.'));
        }

        $before = $this->userAuditSnapshot($user);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->syncRoles([$validated['role']]);
        $this->forgetPermissionCache();
        $user->refresh();
        $user->load('roles');

        $after = $this->userAuditSnapshot($user);

        $this->audit->log(
            description: __('User :email updated', ['email' => $user->email]),
            event: 'users.updated',
            subject: $user,
            properties: [
                'group' => 'users',
                'before' => $before,
                'after' => $after,
            ],
            logName: AuditService::LOG_SECURITY,
        );

        return redirect()->route('lfs-administration.settings.users')->with('success', __('User updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === Auth::id()) {
            return redirect()->route('lfs-administration.settings.users')->with('error', __('You cannot delete your own account.'));
        }

        if (UserPolicy::wouldDeleteLastSuperAdmin($user)) {
            return redirect()->route('lfs-administration.settings.users')->with('error', __('Cannot delete the last Super Admin.'));
        }

        $before = $this->userAuditSnapshot($user);
        $email = $user->email;

        try {
            $user->delete();
        } catch (QueryException) {
            return redirect()->route('lfs-administration.settings.users')->with('error', __('This user cannot be deleted because related records exist.'));
        }

        $this->forgetPermissionCache();

        $this->audit->log(
            description: __('User :email deleted', ['email' => $email]),
            event: 'users.deleted',
            subject: null,
            properties: [
                'group' => 'users',
                'before' => $before,
            ],
            logName: AuditService::LOG_SECURITY,
        );

        return redirect()->route('lfs-administration.settings.users')->with('success', __('User deleted.'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        if ($user->id === Auth::id()) {
            return redirect()->route('lfs-administration.settings.users')->with('error', __('You cannot deactivate your own account.'));
        }

        $newActive = ! $user->is_active;

        if (! $newActive && $user->hasRole(UserPolicy::ROLE_SUPER_ADMIN) && UserPolicy::superAdminCount() <= 1) {
            return redirect()->route('lfs-administration.settings.users')->with('error', __('Cannot deactivate the last Super Admin.'));
        }

        $before = $this->userAuditSnapshot($user);
        $user->is_active = $newActive;
        $user->save();
        $user->refresh();
        $user->load('roles');
        $after = $this->userAuditSnapshot($user);

        $this->audit->log(
            description: __('User :email active flag changed', ['email' => $user->email]),
            event: 'users.active_toggled',
            subject: $user,
            properties: [
                'group' => 'users',
                'before' => $before,
                'after' => $after,
            ],
            logName: AuditService::LOG_SECURITY,
        );

        return redirect()->route('lfs-administration.settings.users')->with('success', __('User status updated.'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Spatie\Permission\Models\Role>
     */
    protected function assignableRoles(?User $actor): \Illuminate\Support\Collection
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();

        if (! $actor?->hasRole(UserPolicy::ROLE_SUPER_ADMIN)) {
            return $roles->filter(fn (Role $r) => $r->name !== UserPolicy::ROLE_SUPER_ADMIN)->values();
        }

        return $roles;
    }

    /**
     * @return array<int, string>
     */
    protected function assignableRoleNames(?User $actor): array
    {
        return $this->assignableRoles($actor)->pluck('name')->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function userAuditSnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'department' => $user->department,
            'position' => $user->position,
            'roles' => $user->roles->pluck('name')->sort()->values()->all(),
        ];
    }

    protected function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
