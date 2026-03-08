<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Record movement') }}</h2></x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('inventory-valuation.movements.store') }}">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="warehouse_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Warehouse') }} *</label>
                            <select id="warehouse_id" name="warehouse_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->code }} - {{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="item_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Item') }} *</label>
                            <select id="item_id" name="item_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                @foreach($items as $i)
                                    <option value="{{ $i->id }}" {{ old('item_id') == $i->id ? 'selected' : '' }}>{{ $i->code }} - {{ $i->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="movement_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Type') }} *</label>
                            <select id="movement_type" name="movement_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="receipt" {{ old('movement_type') === 'receipt' ? 'selected' : '' }}>{{ __('Receipt') }}</option>
                                <option value="issue" {{ old('movement_type') === 'issue' ? 'selected' : '' }}>{{ __('Issue') }}</option>
                                <option value="transfer_in" {{ old('movement_type') === 'transfer_in' ? 'selected' : '' }}>{{ __('Transfer in') }}</option>
                                <option value="transfer_out" {{ old('movement_type') === 'transfer_out' ? 'selected' : '' }}>{{ __('Transfer out') }}</option>
                                <option value="adjustment" {{ old('movement_type') === 'adjustment' ? 'selected' : '' }}>{{ __('Adjustment') }}</option>
                                <option value="write_off" {{ old('movement_type') === 'write_off' ? 'selected' : '' }}>{{ __('Write-off') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="movement_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }} *</label>
                            <input type="date" id="movement_date" name="movement_date" value="{{ old('movement_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Quantity') }} *</label>
                            <input type="number" id="quantity" name="quantity" step="0.0001" value="{{ old('quantity') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <p class="mt-1 text-xs text-gray-500">{{ __('Use negative for issue, transfer out, write-off') }}</p>
                        </div>
                        <div>
                            <label for="unit_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Unit cost') }}</label>
                            <input type="number" id="unit_cost" name="unit_cost" step="0.0001" min="0" value="{{ old('unit_cost', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reference') }}</label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Record') }}</button>
                    <a href="{{ route('inventory-valuation.movements.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
