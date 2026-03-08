<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('General Ledger') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-2 text-gray-900 dark:text-gray-100">{{ __('Reports') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Use the links below to access core financial reports powered by the journal engine.') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('general-ledger.trial-balance') }}"
                       class="flex items-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-balance-scale text-2xl text-blue-600 dark:text-blue-400 mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Trial Balance') }}</span>
                    </a>
                    <a href="{{ route('general-ledger.ledger') }}"
                       class="flex items-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-book-open text-2xl text-green-600 dark:text-green-400 mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('General Ledger') }}</span>
                    </a>
                    <a href="{{ route('general-ledger.income-statement') }}"
                       class="flex items-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-file-invoice-dollar text-2xl text-amber-600 dark:text-amber-400 mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Income Statement') }}</span>
                    </a>
                    <a href="{{ route('general-ledger.balance-sheet') }}"
                       class="flex items-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-balance-scale-right text-2xl text-purple-600 dark:text-purple-400 mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Balance Sheet') }}</span>
                    </a>
                    <a href="{{ route('general-ledger.cash-flow') }}"
                       class="flex items-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-money-bill-wave text-2xl text-teal-600 dark:text-teal-400 mr-3"></i>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Cash Flow Statement') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

