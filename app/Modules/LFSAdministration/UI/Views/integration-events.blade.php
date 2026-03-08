<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Financial Events Monitor') }}</h2>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('lfs-administration.integration-events') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Event type') }}</label>
                    <input type="text" id="event_type" name="event_type" value="{{ request('event_type') }}" placeholder="e.g. shipment-delivered" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-48">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                    <select id="status" name="status" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="posted" @selected(request('status') === 'posted')>{{ __('Posted') }}</option>
                        <option value="accepted" @selected(request('status') === 'accepted')>{{ __('Accepted') }}</option>
                        <option value="duplicate" @selected(request('status') === 'duplicate')>{{ __('Duplicate') }}</option>
                        <option value="error" @selected(request('status') === 'error')>{{ __('Error') }}</option>
                    </select>
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
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Event type') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Source system') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reference') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Journal') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Message') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $log->event_type }}</td>
                                <td class="px-4 py-2">{{ $log->source_system }}</td>
                                <td class="px-4 py-2">{{ $log->source_reference }}</td>
                                <td class="px-4 py-2">
                                    @if($log->status === 'posted')
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">posted</span>
                                    @elseif($log->status === 'accepted')
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">accepted</span>
                                    @elseif($log->status === 'duplicate')
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">duplicate</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">{{ $log->status }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if($log->journal_id)
                                        <a href="{{ route('core-accounting.journals.show', $log->journal_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $log->journal_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 max-w-xs truncate" title="{{ $log->message }}">{{ $log->message ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No integration events yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
