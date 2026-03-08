<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('New purchase order') }}</h2>
            <a href="{{ route('procurement.purchase-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('procurement.purchase-orders.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Vendor') }} *</label>
                        <select id="vendor_id" name="vendor_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">{{ __('Select vendor') }}</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->code }} - {{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="purchase_request_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From P.R. (optional)') }}</label>
                        <select id="purchase_request_id" name="purchase_request_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">{{ __('None') }}</option>
                            @foreach($purchaseRequests ?? [] as $pr)
                                <option value="{{ $pr->id }}" {{ old('purchase_request_id') == $pr->id ? 'selected' : '' }}>{{ $pr->pr_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="order_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Order date') }} *</label>
                        <input type="date" id="order_date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="expected_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Expected date') }}</label>
                        <input type="date" id="expected_date" name="expected_date" value="{{ old('expected_date') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }}</label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Lines') }} *</label>
                    <div class="space-y-2">
                        @php $oldLines = old('lines', [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'account_code' => '']]); @endphp
                        @foreach($oldLines as $i => $line)
                        <div class="flex gap-2 items-end flex-wrap">
                            <input type="text" name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" placeholder="{{ __('Description') }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="number" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" step="0.0001" min="0.0001" class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="number" name="lines[{{ $i }}][unit_price]" value="{{ $line['unit_price'] ?? 0 }}" step="0.01" min="0" class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="text" name="lines[{{ $i }}][account_code]" value="{{ $line['account_code'] ?? '' }}" placeholder="{{ __('Account') }}" class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create P.O.') }}</button>
                    <a href="{{ route('procurement.purchase-orders.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
