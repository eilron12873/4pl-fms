<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Record transaction') }} – {{ $account->name }}</h2></x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('treasury.transactions.store') }}">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $account->id }}">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }} *</label>
                            <input type="date" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @error('transaction_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Type') }} *</label>
                            <select id="type" name="type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="deposit" {{ old('type') === 'deposit' ? 'selected' : '' }}>{{ __('Deposit') }}</option>
                                <option value="withdrawal" {{ old('type') === 'withdrawal' ? 'selected' : '' }}>{{ __('Withdrawal') }}</option>
                                <option value="transfer" {{ old('type') === 'transfer' ? 'selected' : '' }}>{{ __('Transfer') }}</option>
                                <option value="fee" {{ old('type') === 'fee' ? 'selected' : '' }}>{{ __('Fee') }}</option>
                                <option value="adjustment" {{ old('type') === 'adjustment' ? 'selected' : '' }}>{{ __('Adjustment') }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }} *</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }} *</label>
                            <input type="number" id="amount" name="amount" step="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <p class="mt-1 text-xs text-gray-500">{{ __('Use positive for deposit, negative for withdrawal') }}</p>
                            @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reference') }}</label>
                            <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Record') }}</button>
                    <a href="{{ route('treasury.bank-accounts.show', $account->id) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
