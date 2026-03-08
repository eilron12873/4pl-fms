<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Accounts Receivable') }}
        </h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('accounts-receivable.invoices.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-file-invoice text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Invoices') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View and manage invoices') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-receivable.statement') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-file-alt text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Statement of Account') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('By client') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-receivable.aging') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-clock text-2xl text-amber-600 dark:text-amber-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('AR Aging') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Outstanding by age') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-receivable.payments.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-hand-holding-usd text-2xl text-purple-600 dark:text-purple-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Payments') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Payments and collections') }}</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-app-layout>
