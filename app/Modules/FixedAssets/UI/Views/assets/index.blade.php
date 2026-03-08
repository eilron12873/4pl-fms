<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Asset Registry') }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('fixed-assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
                @can('fixed-assets.manage')
                    <a href="{{ route('fixed-assets.assets.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Register asset') }}</a>
                @endcan
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))
            <div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('fixed-assets.assets.index') }}" class="flex flex-wrap gap-4 items-end">
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
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Code') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Purchase date') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Cost') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Accum. depn') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Book value') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($assets as $a)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $a->code }}</td>
                                <td class="px-4 py-2">{{ $a->name }}</td>
                                <td class="px-4 py-2">{{ ucfirst($a->asset_type) }}</td>
                                <td class="px-4 py-2">{{ $a->purchase_date ? $a->purchase_date->format('Y-m-d') : '' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->acquisition_cost, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->accumulated_depreciation, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($a->bookValue(), 2) }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs {{ $a->status === 'active' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-200 dark:bg-gray-600' }}">{{ $a->status }}</span></td>
                                <td class="px-4 py-2"><a href="{{ route('fixed-assets.assets.show', $a->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No assets.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assets->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $assets->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
