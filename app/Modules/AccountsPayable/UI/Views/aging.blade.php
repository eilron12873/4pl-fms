<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('AP Aging') }}</h2>
    </x-slot>
    <div class="py-4 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('accounts-payable.aging') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="as_of_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('As of date') }}</label>
                    <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Apply') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2 text-right font-semibold">{{ __('Current') }}</th>
                            <th class="px-4 py-2 text-right font-semibold">{{ __('1-30 days') }}</th>
                            <th class="px-4 py-2 text-right font-semibold">{{ __('31-60 days') }}</th>
                            <th class="px-4 py-2 text-right font-semibold">{{ __('61-90 days') }}</th>
                            <th class="px-4 py-2 text-right font-semibold">{{ __('Over 90') }}</th>
                            <th class="px-4 py-2 text-right font-semibold">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($rows as $r)
                            <tr>
                                <td class="px-4 py-2">{{ $r['vendor_code'] }} - {{ $r['vendor_name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['current'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_30'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_60'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['days_90'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r['over_90'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($r['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No outstanding balances.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
