<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Record payment') }}</h2>
    </x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('accounts-payable.payments.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Vendor') }} *</label>
                        <select id="vendor_id" name="vendor_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">{{ __('Select vendor') }}</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->code }} - {{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment date') }} *</label>
                            <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @error('payment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }} *</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }} *</label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment method') }}</label>
                        <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="ach" {{ old('payment_method', 'ach') === 'ach' ? 'selected' : '' }}>{{ __('ACH / Other') }}</option>
                            <option value="check" {{ old('payment_method') === 'check' ? 'selected' : '' }}>{{ __('Check') }}</option>
                        </select>
                    </div>
                    <div id="bank_account_wrap" style="{{ old('payment_method', 'ach') === 'check' ? '' : 'display:none' }}">
                        <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bank account') }}</label>
                        <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">{{ __('Select bank') }}</option>
                            @foreach($bankAccounts ?? [] as $ba)
                                <option value="{{ $ba->id }}" {{ old('bank_account_id') == $ba->id ? 'selected' : '' }}>{{ $ba->name }} ({{ $ba->account_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reference') }}</label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes') }}</textarea>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Allocate to bills in a follow-up step or leave allocations empty.') }}</p>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Record payment') }}</button>
                    <a href="{{ route('accounts-payable.payments.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('payment_method').addEventListener('change', function() {
        document.getElementById('bank_account_wrap').style.display = this.value === 'check' ? 'block' : 'none';
    });
    </script>
</x-app-layout>
