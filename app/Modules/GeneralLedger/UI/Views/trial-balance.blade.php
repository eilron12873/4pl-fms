<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Trial Balance') }}
            </h2>
            <a href="{{ route('general-ledger.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to General Ledger') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('general-ledger.trial-balance') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period') }}</label>
                        <select id="period" name="period" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">{{ __('All / Custom') }}</option>
                            @foreach($periods ?? [] as $p)
                                <option value="{{ $p->code }}" @selected(($from_date && $to_date && $from_date === $p->start_date?->toDateString() && $to_date === $p->end_date?->toDateString()))>{{ $p->code }} ({{ $p->start_date?->format('M j') }} – {{ $p->end_date?->format('M j, Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                        <input type="date" id="from_date" name="from_date" value="{{ $from_date ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    </div>
                    <div>
                        <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                        <input type="date" id="to_date" name="to_date" value="{{ $to_date ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        {{ __('Apply') }}
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 overflow-x-auto">
                    @if($from_date && $to_date)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('Period') }}: {{ $from_date }} – {{ $to_date }}</p>
                    @endif
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Account Code') }}</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Account Name') }}</th>
                                @if($from_date && $to_date)
                                    <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Opening Debit') }}</th>
                                    <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Opening Credit') }}</th>
                                @endif
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Debit') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Credit') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Net Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                            @php
                                $totalDebit = 0;
                                $totalCredit = 0;
                            @endphp
                            @forelse ($rows as $row)
                                @php
                                    $totalDebit += $row['debit'];
                                    $totalCredit += $row['credit'];
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200 font-mono">{{ $row['account']->code }}</td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $row['account']->name }}</td>
                                    @if($from_date && $to_date)
                                        <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($row['opening_debit'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($row['opening_credit'] ?? 0, 2) }}</td>
                                    @endif
                                    <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($row['debit'], 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($row['credit'], 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($row['balance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($from_date && $to_date) ? 7 : 5 }}" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('No journal data available yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th colspan="{{ ($from_date && $to_date) ? 4 : 2 }}" class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ __('Totals') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalDebit ?? 0, 2) }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalCredit ?? 0, 2) }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format(($totalDebit ?? 0) - ($totalCredit ?? 0), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
