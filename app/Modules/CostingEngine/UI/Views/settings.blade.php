<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Costing Settings') }}</h2>
            <a href="{{ route('costing-engine.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Costing') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('costing-engine.settings.update') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Revenue account prefixes (CSV)') }}</label>
                    <input type="text" name="revenue_prefixes" value="{{ old('revenue_prefixes', $revenuePrefixes) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Expense account prefixes (CSV)') }}</label>
                    <input type="text" name="expense_prefixes" value="{{ old('expense_prefixes', $expensePrefixes) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Enabled dimensions (CSV)') }}</label>
                    <input type="text" name="enabled_dimensions" value="{{ old('enabled_dimensions', $enabledDimensions) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Functional currency') }}</label>
                    <input type="text" name="functional_currency" value="{{ old('functional_currency', $functionalCurrency) }}" maxlength="3" class="mt-1 block w-32 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Save settings') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>

