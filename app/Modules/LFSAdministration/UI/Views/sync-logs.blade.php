<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Sync Logs') }}</h2>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('Successful posting sources from financial events API and internal integrations. Idempotency prevents duplicate posting.') }}</p>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('lfs-administration.sync-logs') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="source_system" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Source system') }}</label>
                    <input type="text" id="source_system" name="source_system" value="{{ request('source_system') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-40">
                </div>
                <div>
                    <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Event type') }}</label>
                    <input type="text" id="event_type" name="event_type" value="{{ request('event_type') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-40">
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
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Idempotency key') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Journal') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($sources as $src)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $src->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $src->event_type ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $src->source_system }}</td>
                                <td class="px-4 py-2">{{ $src->source_reference }}</td>
                                <td class="px-4 py-2 font-mono text-xs max-w-[12rem] truncate" title="{{ $src->idempotency_key }}">{{ $src->idempotency_key }}</td>
                                <td class="px-4 py-2">
                                    @if($src->journal_id && $src->journal)
                                        <a href="{{ route('core-accounting.journals.show', $src->journal_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $src->journal->journal_number ?? $src->journal_id }}</a>
                                    @else
                                        <span class="text-gray-400">{{ __('—') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No sync log entries yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sources->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $sources->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
