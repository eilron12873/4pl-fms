<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Journal Management') }}
            </h2>
            <a href="{{ route('core-accounting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Core Accounting') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Number') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Date') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Period') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Description') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Lines') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($journals as $journal)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-mono">{{ $journal->journal_number }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $journal->journal_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $journal->period ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm max-w-xs truncate">{{ $journal->description ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-0.5 rounded text-xs {{ $journal->status === 'posted' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $journal->status }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ $journal->lines_count }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('core-accounting.journals.show', $journal->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No journals yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($journals->hasPages())
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                        {{ $journals->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
