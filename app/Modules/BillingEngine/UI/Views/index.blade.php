<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Billing Engine') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('billing-engine.clients.index') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-user-friends text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Billing Clients') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage clients and external IDs') }}</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('billing-engine.contracts.index') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-file-contract text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Contracts & Rates') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Contracts and rate definitions') }}</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('billing-engine.rate-simulation') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-calculator text-2xl text-amber-600 dark:text-amber-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Rate Simulation') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Simulate pricing by event type') }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
