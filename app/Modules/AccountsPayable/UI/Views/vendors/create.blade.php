<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Add vendor') }}</h2>
    </x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('accounts-payable.vendors.store') }}">
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
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Category') }}</label>
                            <input type="text" id="category" name="category" value="{{ old('category') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="{{ __('Transport, Customs, Warehouse...') }}">
                            @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tax ID') }}</label>
                            <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @error('tax_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }} *</label>
                            <input type="text" id="currency" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                        <div>
                            <label for="payment_terms_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment terms (days)') }}</label>
                            <input type="number" id="payment_terms_days" name="payment_terms_days" value="{{ old('payment_terms_days', 30) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="preferred_payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Preferred payment method') }}</label>
                            <select id="preferred_payment_method" name="preferred_payment_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">{{ __('Not specified') }}</option>
                                <option value="ach" @selected(old('preferred_payment_method') === 'ach')>{{ __('ACH / Bank transfer') }}</option>
                                <option value="check" @selected(old('preferred_payment_method') === 'check')>{{ __('Check') }}</option>
                                <option value="other" @selected(old('preferred_payment_method') === 'other')>{{ __('Other') }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes') }}</textarea>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __('Bank details (optional)') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bank name') }}</label>
                                <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            </div>
                            <div>
                                <label for="bank_account_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bank account number') }}</label>
                                <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            </div>
                            <div>
                                <label for="bank_swift_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SWIFT / BIC') }}</label>
                                <input type="text" id="bank_swift_code" name="bank_swift_code" value="{{ old('bank_swift_code') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create vendor') }}</button>
                    <a href="{{ route('accounts-payable.vendors.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
