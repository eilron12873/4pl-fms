<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Management P&L by dimension') }}</h2>
            <a href="{{ route('financial-reporting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Financial Reporting') }}</a>
        </div>
    </x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('financial-reporting.management-pl-dimension') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="dimension" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Dimension') }}</label>
                    <select id="dimension" name="dimension" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="client_id" @selected(($data['dimension'] ?? '') === 'client_id')>{{ __('Client') }}</option>
                        <option value="warehouse_id" @selected(($data['dimension'] ?? '') === 'warehouse_id')>{{ __('Warehouse') }}</option>
                        <option value="project_id" @selected(($data['dimension'] ?? '') === 'project_id')>{{ __('Project') }}</option>
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
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('P&L by dimension') }} — {{ $data['from_date'] ?? '' }} {{ __('to') }} {{ $data['to_date'] ?? '' }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Dimension') }}</th>
                            @foreach($data['section_keys'] ?? [] as $key)
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ $data['section_labels'][$key] ?? $key }}</th>
                            @endforeach
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Net income') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($data['rows'] ?? [] as $row)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $data['dimension_labels'][$row['dimension_id']] ?? $row['dimension_id'] }}</td>
                                @foreach($data['section_keys'] ?? [] as $key)
                                    <td class="px-4 py-2 text-right">{{ number_format($row['sections'][$key]['amount'] ?? 0, 2) }}</td>
                                @endforeach
                                <td class="px-4 py-2 text-right font-medium {{ $row['net_income'] >= 0 ? '' : 'text-red-600 dark:text-red-400' }}">{{ number_format($row['net_income'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No data. Post journal lines with the selected dimension.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
