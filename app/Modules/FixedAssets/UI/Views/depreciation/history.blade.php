<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Depreciation history') }}</h2>
            <a href="{{ route('fixed-assets.depreciation.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to depreciation') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Posted depreciation journals from Fixed Assets.') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Journal') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($sources as $source)
                            @php
                                $journal = $source->journal;
                                $amount = $journal && $journal->lines ? $journal->lines->sum('debit') : 0;
                                $asset = $assets->get((int) $source->source_reference);
                            @endphp
                            <tr>
                                <td class="px-4 py-2">{{ $journal && $journal->journal_date ? $journal->journal_date->format('Y-m-d') : '-' }}</td>
                                <td class="px-4 py-2">
                                    @if($asset)
                                        <a href="{{ route('fixed-assets.assets.show', $asset->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $asset->code }}</a>
                                        - {{ $asset->name }}
                                    @else
                                        {{ $source->source_reference }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $journal ? $journal->journal_number : '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($amount, 2) }}</td>
                                <td class="px-4 py-2">
                                    @if($journal)
                                        <a href="{{ route('core-accounting.journals.show', $journal->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-xs">{{ __('View journal') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No depreciation history. Run depreciation from the Depreciation page.') }}</td></tr>
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
