<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('AR/AP KPI dashboard') }}</h2>
            <a href="{{ route('financial-reporting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Financial Reporting') }}</a>
        </div>
    </x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('financial-reporting.kpi-dashboard') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="as_of_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Aging as of') }}</label>
                    <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period from') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ $fromDate ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period to') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ $toDate ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Apply') }}</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('AR total outstanding') }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($arTotal ?? 0, 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('As of') }} {{ $asOfDate ?? '' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('AP total outstanding') }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($apTotal ?? 0, 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('As of') }} {{ $asOfDate ?? '' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('DSO (days sales outstanding)') }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $dso !== null ? round($dso, 0) : '—' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('Based on AR and period revenue') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Margin variance (vs prior period)') }}</p>
                <p class="text-2xl font-semibold mt-1 {{ ($marginVariancePct ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $marginVariancePct !== null ? number_format($marginVariancePct, 1) . '%' : '—' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('Current margin %') }}: {{ $marginPctCurrent !== null ? number_format($marginPctCurrent, 1) . '%' : '—' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('AR aging summary') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('As of') }} {{ $asOfDate ?? '' }} · <a href="{{ route('accounts-receivable.aging', ['as_of_date' => $asOfDate]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Full AR aging') }}</a></p>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Client') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Current') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">1-30</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">31-60</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">61-90</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">90+</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($arAging->take(10) as $r)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $r['client_code'] ?? '' }} - {{ $r['client_name'] ?? '' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['current'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_30'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_60'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_90'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['over_90'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($r['total'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($arAging->count() > 10)
                    <p class="text-sm text-gray-500 mt-2">{{ __('Showing first 10.') }} <a href="{{ route('accounts-receivable.aging') }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a></p>
                @endif
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('AP aging summary') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('As of') }} {{ $asOfDate ?? '' }} · <a href="{{ route('accounts-payable.aging', ['as_of_date' => $asOfDate]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Full AP aging') }}</a></p>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Current') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">1-30</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">31-60</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">61-90</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">90+</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($apAging->take(10) as $r)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $r['vendor_code'] ?? '' }} - {{ $r['vendor_name'] ?? '' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['current'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_30'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_60'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_90'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['over_90'] ?? 0, 2) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($r['total'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($apAging->count() > 10)
                    <p class="text-sm text-gray-500 mt-2">{{ __('Showing first 10.') }} <a href="{{ route('accounts-payable.aging') }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a></p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
