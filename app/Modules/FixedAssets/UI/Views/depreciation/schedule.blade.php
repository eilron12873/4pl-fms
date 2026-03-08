<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Depreciation schedule') }}</h2>
            <a href="{{ route('fixed-assets.depreciation.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to depreciation') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('fixed-assets.depreciation.schedule') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="asset_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Type') }}</label>
                    <select id="asset_type" name="asset_type" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="vehicle" @selected(request('asset_type') === 'vehicle')>{{ __('Vehicle') }}</option>
                        <option value="equipment" @selected(request('asset_type') === 'equipment')>{{ __('Equipment') }}</option>
                        <option value="it" @selected(request('asset_type') === 'it')>{{ __('IT') }}</option>
                        <option value="building" @selected(request('asset_type') === 'building')>{{ __('Building') }}</option>
                        <option value="other" @selected(request('asset_type') === 'other')>{{ __('Other') }}</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Straight-line schedule for active assets. Monthly and annual depreciation, remaining months.') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Cost') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Residual') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Monthly depn') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Annual depn') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Accumulated') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Book value') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Remaining months') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Last depn') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($schedule as $row)
                            @php $a = $row['asset']; @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <a href="{{ route('fixed-assets.assets.show', $a->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $a->code }}</a>
                                    <span class="text-gray-600 dark:text-gray-400"> - {{ $a->name }}</span>
                                </td>
                                <td class="px-4 py-2">{{ ucfirst($a->asset_type) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->acquisition_cost, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->residual_value, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['monthly_depn'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['annual_depn'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->accumulated_depreciation, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->bookValue(), 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['remaining_months'] }}</td>
                                <td class="px-4 py-2">{{ $a->last_depreciation_at ? $a->last_depreciation_at->format('Y-m-d') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No active assets.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
