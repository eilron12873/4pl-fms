<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $mode === 'create' ? __('New tax code') : __('Edit tax code') }}
            </h2>
            <a href="{{ route('lfs-administration.settings.tax') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ $mode === 'create' ? route('lfs-administration.settings.tax.codes.store') : route('lfs-administration.settings.tax.codes.update', $taxCode) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf
            @if($mode === 'edit')
                @method('PUT')
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Code') }}</label>
                    <input type="text" name="code" value="{{ old('code', $taxCode->code) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm uppercase">
                    @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $taxCode->name) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Type') }}</label>
                <select name="type" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    @foreach(['vat' => 'VAT', 'wht' => __('Withholding'), 'other' => __('Other')] as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', $taxCode->type) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_inclusive" value="0">
                <input type="checkbox" name="is_inclusive" value="1" id="is_inclusive" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700" @checked(old('is_inclusive', $taxCode->is_inclusive))>
                <label for="is_inclusive" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Prices are tax-inclusive') }}</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Rounding mode') }}</label>
                <input type="text" name="rounding_mode" value="{{ old('rounding_mode', $taxCode->rounding_mode) }}" placeholder="e.g. half_up" class="w-full max-w-md rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Input VAT / AP account') }}</label>
                    <select name="input_account_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected((string)old('input_account_id', $taxCode->input_account_id) === (string)$a->id)>{{ $a->code }} — {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Output VAT / AR account') }}</label>
                    <select name="output_account_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected((string)old('output_account_id', $taxCode->output_account_id) === (string)$a->id)>{{ $a->code }} — {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700" @checked(old('is_active', $taxCode->is_active ?? true))>
                <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</label>
            </div>
            <div class="pt-2">
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
