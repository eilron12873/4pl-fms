<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Add inventory item') }}</h2></x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('inventory-valuation.items.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Code') }} *</label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }} *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SKU') }}</label>
                            <input type="text" id="sku" name="sku" value="{{ old('sku') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                        <div>
                            <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Unit') }}</label>
                            <input type="text" id="unit" name="unit" value="{{ old('unit', 'EA') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                    <div>
                        <label for="valuation_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Valuation method') }}</label>
                        <select id="valuation_method" name="valuation_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="weighted_avg" {{ old('valuation_method', 'weighted_avg') === 'weighted_avg' ? 'selected' : '' }}>{{ __('Weighted average') }}</option>
                            <option value="fifo" {{ old('valuation_method') === 'fifo' ? 'selected' : '' }}>{{ __('FIFO') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create') }}</button>
                    <a href="{{ route('inventory-valuation.items.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
