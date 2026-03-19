<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Profitability Details') }}</h2>
            <a href="{{ route('costing-engine.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Costing') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('Dimension: :dim | ID: :id', ['dim' => $dimension, 'id' => $dimensionId]) }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ __('Period: :from to :to', ['from' => $fromDate ?? __('All'), 'to' => $toDate ?? __('All')]) }}
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Journal lines') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ __('Date') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Journal') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Account') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Description') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Debit') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Credit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($details['journal_lines'] as $line)
                            <tr>
                                <td class="px-3 py-2">{{ \Carbon\Carbon::parse($line->journal_date)->format('Y-m-d') }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('core-accounting.journals.show', $line->journal_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $line->journal_number }}
                                    </a>
                                </td>
                                <td class="px-3 py-2">{{ $line->account_code }} - {{ $line->account_name }}</td>
                                <td class="px-3 py-2">{{ $line->description }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->debit, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($line->credit, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No journal lines found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($details['invoices']) && $details['invoices']->count() > 0)
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('AR invoices') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ __('Invoice') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Date') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Status') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($details['invoices'] as $inv)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('accounts-receivable.invoices.show', $inv->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">{{ $inv->invoice_date?->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2">{{ $inv->status }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($inv->total, 2) }} {{ $inv->currency }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

