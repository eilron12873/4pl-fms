<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Cash flow analysis') }}</h2>
            <a href="{{ route('financial-reporting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Financial Reporting') }}</a>
        </div>
    </x-slot>
    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('financial-reporting.cash-flow-analysis') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ $from_date ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ $to_date ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Apply') }}</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('GL Cash flow (indirect)') }} — {{ $from_date ?? '' }} to {{ $to_date ?? '' }}</h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ __('Net income') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($glCashFlow['net_income'] ?? 0, 2) }}</td>
                        </tr>
                        @foreach($glCashFlow['operating'] ?? [] as $adj)
                            <tr>
                                <td class="px-4 py-2 pl-6 text-gray-600 dark:text-gray-300">{{ $adj['label'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($adj['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Cash from operations') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($glCashFlow['cash_from_operations'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ __('Investing') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($glCashFlow['investing'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ __('Financing') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($glCashFlow['financing'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="font-bold border-t-2 border-gray-300 dark:border-gray-500">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Net change in cash (GL)') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($glCashFlow['net_change_cash'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Treasury cash position') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Bank account balances (opening + transactions). Compare with GL cash accounts for reconciliation.') }}</p>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Account') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Balance') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Currency') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($treasuryPosition['accounts'] ?? [] as $acc)
                            @php $bal = (float)($acc->opening_balance ?? 0) + (float)($acc->transactions_sum_amount ?? 0); @endphp
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $acc->name }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($bal, 2) }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $acc->currency ?? 'USD' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(!empty($treasuryPosition['total_by_currency']))
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Total by currency') }}</p>
                        @foreach($treasuryPosition['total_by_currency'] as $currency => $total)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $currency }}: {{ number_format($total, 2) }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
