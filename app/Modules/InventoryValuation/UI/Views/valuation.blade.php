<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Inventory Valuation') }}</h2>
            <a href="{{ route('inventory-valuation.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('inventory-valuation.valuation') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected($warehouseId == $w->id)>{{ $w->code }} - {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="item_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Item') }}</label>
                    <select id="item_id" name="item_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($items as $i)
                            <option value="{{ $i->id }}" @selected($itemId == $i->id)>{{ $i->code }} - {{ $i->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Apply') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Total value') }}: {{ number_format($totalValue ?? 0, 2) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Warehouse') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Item') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Quantity') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Unit cost') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($report as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row['warehouse_code'] }} - {{ $row['warehouse_name'] }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row['item_code'] }} - {{ $row['item_name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['quantity'], 4) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['unit_cost'], 4) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($row['value'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No inventory balances.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
