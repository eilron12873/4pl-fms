<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('New purchase request') }}</h2>
            <a href="{{ route('procurement.purchase-requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('procurement.purchase-requests.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="requested_by" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Requested by') }}</label>
                        <input type="text" id="requested_by" name="requested_by" value="{{ old('requested_by') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Department') }}</label>
                        <input type="text" id="department" name="department" value="{{ old('department') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="request_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Request date') }} *</label>
                        <input type="date" id="request_date" name="request_date" value="{{ old('request_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Lines') }} *</label>
                    <div id="lines-container" class="space-y-2">
                        @foreach(old('lines', [['description' => '', 'quantity' => 1, 'estimated_unit_cost' => 0, 'account_code' => '']]) as $i => $line)
                        <div class="flex gap-2 items-end flex-wrap">
                            <input type="text" name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" placeholder="{{ __('Description') }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="number" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" step="0.0001" min="0.0001" placeholder="Qty" class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="number" name="lines[{{ $i }}][estimated_unit_cost]" value="{{ $line['estimated_unit_cost'] ?? 0 }}" step="0.01" min="0" placeholder="{{ __('Est. cost') }}" class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="text" name="lines[{{ $i }}][account_code]" value="{{ $line['account_code'] ?? '' }}" placeholder="{{ __('Account') }}" class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        </div>
                        @endforeach
                        @if(count(old('lines', [])) === 0)
                        <div class="flex gap-2 items-end flex-wrap">
                            <input type="text" name="lines[0][description]" placeholder="{{ __('Description') }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="number" name="lines[0][quantity]" value="1" step="0.0001" min="0.0001" class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="number" name="lines[0][estimated_unit_cost]" value="0" step="0.01" min="0" class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required>
                            <input type="text" name="lines[0][account_code]" placeholder="{{ __('Account') }}" class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        </div>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create P.R.') }}</button>
                    <a href="{{ route('procurement.purchase-requests.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
