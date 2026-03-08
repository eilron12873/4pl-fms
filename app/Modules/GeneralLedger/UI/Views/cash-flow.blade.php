<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Cash Flow Statement') }}
            </h2>
            <a href="{{ route('general-ledger.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to General Ledger') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('general-ledger.cash-flow') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period') }}</label>
                        <select id="period" name="period" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    {{ __('Cash Flow Statement (Indirect)') }} — {{ $data['from_date'] ?? '' }} {{ __('to') }} {{ $data['to_date'] ?? '' }}
                </h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ __('Net Income') }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($data['net_income'] ?? 0, 2) }}</td>
                        </tr>
                        @foreach($data['operating'] ?? [] as $adj)
                            <tr>
                                <td class="px-4 py-2 pl-6 text-gray-600 dark:text-gray-300">{{ $adj['label'] }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ number_format($adj['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Cash from Operations') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['cash_from_operations'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ __('Investing activities') }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($data['investing'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ __('Financing activities') }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($data['financing'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="font-bold border-t-2 border-gray-300 dark:border-gray-500">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ __('Net change in cash') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($data['net_change_cash'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
