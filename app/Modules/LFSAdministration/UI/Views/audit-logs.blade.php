<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Audit Logs') }}</h2>
            <div class="flex items-center gap-3">
                @php
                    $exportQuery = array_filter([
                        'log_name' => request('log_name'),
                        'event' => request('event'),
                        'from_date' => request('from_date'),
                        'to_date' => request('to_date'),
                        'causer_id' => request('causer_id'),
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                @if(config('audit.export.require_date_range') && (empty(request('from_date')) || empty(request('to_date'))))
                    <span class="text-xs text-gray-500 dark:text-gray-400" title="{{ __('Set from and to date to enable CSV export.') }}">{{ __('Export: set date range') }}</span>
                @else
                    <a href="{{ route('lfs-administration.audit-logs.export', $exportQuery) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Export CSV') }}</a>
                @endif
                <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('lfs-administration.audit-logs') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="log_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Category') }}</label>
                    <select id="log_name" name="log_name" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="financial" @selected(request('log_name') === 'financial')>{{ __('Financial') }}</option>
                        <option value="default" @selected(request('log_name') === 'default')>{{ __('Default') }}</option>
                        <option value="audit" @selected(request('log_name') === 'audit')>{{ __('Audit') }}</option>
                        <option value="security" @selected(request('log_name') === 'security')>{{ __('Security') }}</option>
                        <option value="configuration" @selected(request('log_name') === 'configuration')>{{ __('Configuration') }}</option>
                        <option value="procurement" @selected(request('log_name') === 'procurement')>{{ __('Procurement') }}</option>
                    </select>
                </div>
                <div>
                    <label for="event" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Event') }}</label>
                    <input type="text" id="event" name="event" value="{{ request('event') }}" placeholder="{{ __('Partial match') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-40">
                </div>
                <div>
                    <label for="causer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('User ID') }}</label>
                    <input type="number" min="1" id="causer_id" name="causer_id" value="{{ request('causer_id') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-28">
                </div>
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Category') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Event') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('User') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($activities as $a)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $a->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $a->log_name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $a->event ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $a->description }}</td>
                                <td class="px-4 py-2">{{ $a->causer?->name ?? $a->causer?->email ?? '—' }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <a href="{{ route('lfs-administration.audit-logs.show', $a) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Details') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No audit entries.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($activities->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $activities->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
