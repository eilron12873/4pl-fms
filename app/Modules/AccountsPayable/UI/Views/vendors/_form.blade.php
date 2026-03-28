@php
    /** @var \App\Modules\AccountsPayable\Infrastructure\Models\Vendor|null $vendor */
    $v = $vendor ?? null;
@endphp
<div class="space-y-4">
    <div>
        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Code') }} *</label>
        <input type="text" id="code" name="code" value="{{ old('code', $v?->code ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }} *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $v?->name ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Category') }}</label>
            <input type="text" id="category" name="category" value="{{ old('category', $v?->category ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="{{ __('Transport, Customs, Warehouse...') }}">
            @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tax ID') }}</label>
            <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $v?->tax_id ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('tax_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }} *</label>
            <input type="text" id="currency" name="currency" value="{{ old('currency', $v?->currency ?? 'USD') }}" maxlength="3" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="payment_terms_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment terms (days)') }}</label>
            <input type="number" id="payment_terms_days" name="payment_terms_days" value="{{ old('payment_terms_days', $v?->payment_terms_days ?? 30) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('payment_terms_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="preferred_payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Preferred payment method') }}</label>
            <select id="preferred_payment_method" name="preferred_payment_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('Not specified') }}</option>
                <option value="ach" @selected(old('preferred_payment_method', $v?->preferred_payment_method ?? '') === 'ach')>{{ __('ACH / Bank transfer') }}</option>
                <option value="check" @selected(old('preferred_payment_method', $v?->preferred_payment_method ?? '') === 'check')>{{ __('Check') }}</option>
                <option value="other" @selected(old('preferred_payment_method', $v?->preferred_payment_method ?? '') === 'other')>{{ __('Other') }}</option>
            </select>
            @error('preferred_payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-end pb-1">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600" @checked((string) old('is_active', ($v?->is_active ?? true) ? '1' : '0') === '1')>
                {{ __('Active vendor') }}
            </label>
        </div>
    </div>
    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes', $v?->notes ?? '') }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-2">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __('Bank details (optional)') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bank name') }}</label>
                <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $v?->bank_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_account_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bank account number') }}</label>
                <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $v?->bank_account_number ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('bank_account_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_swift_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SWIFT / BIC') }}</label>
                <input type="text" id="bank_swift_code" name="bank_swift_code" value="{{ old('bank_swift_code', $v?->bank_swift_code ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('bank_swift_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>
</div>
