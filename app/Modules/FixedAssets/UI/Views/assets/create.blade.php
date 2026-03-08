<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Register asset') }}</h2>
            <a href="{{ route('fixed-assets.assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('fixed-assets.assets.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Code') }} *</label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" required maxlength="50" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="asset_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Type') }} *</label>
                        <select id="asset_type" name="asset_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="vehicle" {{ old('asset_type') === 'vehicle' ? 'selected' : '' }}>{{ __('Vehicle') }}</option>
                            <option value="equipment" {{ old('asset_type') === 'equipment' ? 'selected' : '' }}>{{ __('Equipment') }}</option>
                            <option value="it" {{ old('asset_type') === 'it' ? 'selected' : '' }}>{{ __('IT') }}</option>
                            <option value="building" {{ old('asset_type') === 'building' ? 'selected' : '' }}>{{ __('Building') }}</option>
                            <option value="other" {{ old('asset_type') === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                        </select>
                        @error('asset_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Purchase date') }} *</label>
                        <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('purchase_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="acquisition_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Acquisition cost') }} *</label>
                        <input type="number" id="acquisition_cost" name="acquisition_cost" step="0.01" min="0" value="{{ old('acquisition_cost') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('acquisition_cost')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="useful_life_years" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Useful life (years)') }} *</label>
                        <input type="number" id="useful_life_years" name="useful_life_years" min="1" max="100" value="{{ old('useful_life_years', 5) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('useful_life_years')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="residual_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Residual value') }}</label>
                        <input type="number" id="residual_value" name="residual_value" step="0.01" min="0" value="{{ old('residual_value', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('residual_value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Location') }}</label>
                        <input type="text" id="location" name="location" value="{{ old('location') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('location')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="custodian" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Custodian') }}</label>
                        <input type="text" id="custodian" name="custodian" value="{{ old('custodian') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('custodian')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Notes') }}</label>
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Register') }}</button>
                    <a href="{{ route('fixed-assets.assets.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
