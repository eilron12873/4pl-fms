<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Write-Off & Adjustments') }}</h2>
            @can('inventory-valuation.manage')
                <a href="{{ route('inventory-valuation.adjustments.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('New adjustment') }}</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('inventory-valuation.adjustments.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->code }} - {{ $w->name }}</option>
                        @endforeach
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
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Warehouse') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Item') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Qty') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($adjustments as $m)
                            <tr>
                                <td class="px-4 py-2">{{ $m->movement_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">{{ $m->warehouse->code ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $m->item->code ?? '-' }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs">{{ $m->movement_type }}</span></td>
                                <td class="px-4 py-2 text-right {{ $m->quantity >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($m->quantity, 2) }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $m->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No adjustments or write-offs.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($adjustments->hasPages())<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $adjustments->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
