<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Tax Summary') }}</h2>
            <a href="{{ route('financial-reporting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Financial Reporting') }}</a>
        </div>
    </x-slot>
    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('financial-reporting.tax-summary') }}" class="flex flex-wrap gap-4 items-end">
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Tax Summary') }} — {{ $data['from_date'] ?? '' }} {{ __('to') }} {{ $data['to_date'] ?? '' }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Revenue and expense summary for tax reporting. Consult your tax advisor.') }}</p>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Category') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($data['sections'] ?? [] as $section)
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($section['amount'], 2) }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $section['is_revenue'] ? __('Revenue') : __('Expense') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold border-t-2 border-gray-300 dark:border-gray-500">
                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Net Income') }}</td>
                        <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['net_income'] ?? 0, 2) }}</td>
                        <td class="px-4 py-2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
