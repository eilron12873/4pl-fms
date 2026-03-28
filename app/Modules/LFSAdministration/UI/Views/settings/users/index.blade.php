<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Users & Roles') }}</h2>
            <div class="flex items-center gap-3">
                @can('create', App\Models\User::class)
                    <a href="{{ route('lfs-administration.settings.users.create') }}" class="inline-flex px-3 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create user') }}</a>
                @endcan
                <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="p-3 rounded-md bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-3 rounded-md bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Super Admins') }}</div>
                <div class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $stats['super_admin'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Admins') }}</div>
                <div class="text-2xl font-semibold text-purple-600 dark:text-purple-400">{{ $stats['admin'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manager / Supervisor / Finance') }}</div>
                <div class="text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ $stats['manager_supervisor_finance'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Analyst / Staff / other') }}</div>
                <div class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ $stats['other'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="GET" action="{{ route('lfs-administration.settings.users') }}" class="flex gap-2 flex-1 max-w-md">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search name, email, department…') }}" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    <button type="submit" class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">{{ __('Search') }}</button>
                </form>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ trans_choice(':count user|:count users', $users->total()) }}</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left text-gray-600 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('User') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Department') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Position') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($users as $u)
                            @php
                                $primaryRole = $u->roles->first();
                                $roleName = $primaryRole?->name ?? '—';
                                $badgeClass = match ($roleName) {
                                    'Super Admin' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                    'Admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
                                    'Manager' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                    'Supervisor' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
                                    'Accountant' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                    'Analyst' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
                                    'Staff' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                    default => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
                                };
                            @endphp
                            <tr class="text-gray-800 dark:text-gray-200">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $u->name }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs">{{ $u->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">{{ $roleName }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $u->department ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $u->position ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($u->is_active)
                                        <span class="text-green-700 dark:text-green-400 text-xs font-medium">{{ __('Active') }}</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 text-xs font-medium">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    @can('update', $u)
                                        <a href="{{ route('lfs-administration.settings.users.edit', $u) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('toggleActive', $u)
                                        @if ($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('lfs-administration.settings.users.toggle-active', $u) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">{{ $u->is_active ? __('Deactivate') : __('Activate') }}</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('delete', $u)
                                        @if ($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('lfs-administration.settings.users.destroy', $u) }}" class="inline" onsubmit="return confirm(@json(__('Delete this user?')));">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $users->links() }}</div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 space-y-3">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Role definitions') }}</h3>
            <p><span class="font-medium text-red-700 dark:text-red-400">{{ __('Super Admin') }}:</span> {{ __('Full access; only Super Admins may assign or manage other Super Admin accounts.') }}</p>
            <p><span class="font-medium text-purple-700 dark:text-purple-400">{{ __('Admin') }}:</span> {{ __('Operational administration including user management for non–Super Admin accounts.') }}</p>
            <p><span class="font-medium text-blue-700 dark:text-blue-400">{{ __('Manager') }}:</span> {{ __('Broad module access without user administration or integration API permissions (see seeder).') }}</p>
            <p><span class="font-medium text-amber-800 dark:text-amber-300">{{ __('Supervisor') }}:</span> {{ __('All view permissions plus manage on AR, AP, procurement, inventory valuation, and costing.') }}</p>
            <p><span class="font-medium text-emerald-700 dark:text-emerald-400">{{ __('Accountant') }}:</span> {{ __('Core accounting, GL, AR/AP, reporting, treasury, billing, costing, fixed assets, and inventory (read where noted in seeder).') }}</p>
            <p><span class="font-medium text-cyan-700 dark:text-cyan-400">{{ __('Analyst') }}:</span> {{ __('View-only across modules (including reports) for analysis.') }}</p>
            <p><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Staff') }}:</span> {{ __('Limited read access: GL, AR, AP, reporting, procurement.') }}</p>
            <p class="text-gray-500 dark:text-gray-400">{{ __('Role & Permission Management (under Audit & Governance) edits which permissions each role has. This screen assigns a user to a role.') }}</p>
        </div>
    </div>
</x-app-layout>
