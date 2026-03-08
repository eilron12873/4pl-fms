<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('P&L per Revenue') }}</h2>
            <a href="{{ route('financial-reporting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Financial Reporting') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('financial-reporting.pl-per-revenue') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period') }}</label>
                    <select id="period" name="period" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('Custom dates') }}</option>
                        @foreach($periods ?? [] as $p)
                            <option value="{{ $p->code }}" @selected(($data['from_date'] ?? '') === $p->start_date?->toDateString())>{{ $p->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ $data['from_date'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ $data['to_date'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Apply') }}</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('P&L per Revenue') }} — {{ $data['from_date'] ?? '' }} {{ __('to') }} {{ $data['to_date'] ?? '' }}</h3>
            <div class="overflow-x-auto space-y-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Revenue by segment') }}</h4>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Segment') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['revenue_segments'] ?? [] as $seg)
                                <tr>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $seg['label'] }}</td>
                                    <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($seg['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-medium bg-gray-50 dark:bg-gray-700/50">
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Total Revenue') }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['total_revenue'] ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Cost & expenses') }}</h4>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Line') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['expense_sections'] ?? [] as $sec)
                                <tr>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $sec['label'] }}</td>
                                    <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($sec['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Net Income') }}: {{ number_format($data['net_income'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
