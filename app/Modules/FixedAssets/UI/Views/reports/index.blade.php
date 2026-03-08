<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Asset reports') }}</h2>
            <a href="{{ route('fixed-assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('fixed-assets.reports.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                    <select id="status" name="status" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                        <option value="disposed" @selected(request('status') === 'disposed')>{{ __('Disposed') }}</option>
                    </select>
                </div>
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
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Apply') }}</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <h3 class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">{{ __('Asset cost & profitability (cost summary)') }}</h3>
            <p class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Per-asset: acquisition cost, accumulated depreciation, net book value, and total maintenance cost. Use for cost allocation and basic profitability view.') }}</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Acquisition cost') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Accum. depn') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Book value') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total maintenance') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Cost (BV + maintenance)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($costReport as $row)
                            @php $a = $row['asset']; @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <a href="{{ route('fixed-assets.assets.show', $a->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $a->code }}</a>
                                    <span class="text-gray-600 dark:text-gray-400"> - {{ $a->name }}</span>
                                </td>
                                <td class="px-4 py-2">{{ ucfirst($a->asset_type) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['acquisition_cost'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['accumulated_depreciation'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['book_value'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['total_maintenance'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($row['total_cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No assets.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <h3 class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">{{ __('Utilization') }}</h3>
            <p class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Utilization metrics (e.g. hours used, trips, capacity %) can be linked from operations or WMS when available. Below: placeholder by asset.') }}</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-300">{{ __('Utilization') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($assets as $a)
                            <tr>
                                <td class="px-4 py-2">{{ $a->code }} - {{ $a->name }}</td>
                                <td class="px-4 py-2">{{ ucfirst($a->asset_type) }}</td>
                                <td class="px-4 py-2 text-center text-gray-500 dark:text-gray-400">{{ __('Not tracked — link from operations when available') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No assets.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
