<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Journal Approval Queue') }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Approval Workflows') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Journals requiring approval are gated before posting.') }}</p>
            </div>

            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Journal #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Period') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Lines') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($journals as $journal)
                            <tr>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200 font-mono">{{ $journal->journal_number }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $journal->journal_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $journal->period ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $journal->lines?->count() ?? 0 }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $journal->status }}</span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('lfs-administration.approval-workflows.journals.show', $journal->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">{{ __('Review') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No journals pending approval.') }}</td>
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
</x-app-layout>

