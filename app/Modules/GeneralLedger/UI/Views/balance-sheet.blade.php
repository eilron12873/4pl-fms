<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Balance Sheet') }}
            </h2>
            <a href="{{ route('general-ledger.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to General Ledger') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('general-ledger.balance-sheet') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="as_of_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('As of date') }}</label>
                        <input type="date" id="as_of_date" name="as_of_date" value="{{ $data['as_of_date'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Apply') }}</button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    {{ __('Balance Sheet') }} — {{ $data['as_of_date'] ?? '' }}
                </h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                        @foreach($data['sections'] ?? [] as $section)
                            <tr>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                                <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($section['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-bold border-t-2 border-gray-300 dark:border-gray-500">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Total Assets') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['total_assets'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Total Liabilities') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['total_liabilities'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Total Equity') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['total_equity'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
