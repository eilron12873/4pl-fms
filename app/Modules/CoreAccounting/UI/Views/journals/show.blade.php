<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Journal') }}: {{ $journal->journal_number }}
            </h2>
            <a href="{{ route('core-accounting.journals.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Journals') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $journal->journal_date?->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Period') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $journal->period ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                        <dd class="mt-1"><span class="px-2 py-0.5 rounded text-xs {{ $journal->status === 'posted' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $journal->status }}</span></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $journal->description ?? '—' }}</dd>
                    </div>
                    @if($journal->postingSource)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Source') }}</dt>
                            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $journal->postingSource->source_system }} / {{ $journal->postingSource->event_type ?? '—' }} / {{ $journal->postingSource->source_reference }}</dd>
                        </div>
                    @endif
                    @if($journal->reversalLinkAsOriginal?->reversal)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Reversed by') }}</dt>
                            <dd class="mt-1">
                                <a href="{{ route('core-accounting.journals.show', $journal->reversalLinkAsOriginal->reversal->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $journal->reversalLinkAsOriginal->reversal->journal_number }}</a>
                            </dd>
                        </div>
                    @endif
                    @if($journal->reversalLinkAsReversal?->original)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Reversal of') }}</dt>
                            <dd class="mt-1">
                                <a href="{{ route('core-accounting.journals.show', $journal->reversalLinkAsReversal->original->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $journal->reversalLinkAsReversal->original->journal_number }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <h3 class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">{{ __('Journal lines') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Account') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Description') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Debit') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Credit') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($journal->lines as $line)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $line->account->code }} — {{ $line->account->name }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $line->description ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format((float) $line->debit, 2) }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format((float) $line->credit, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
