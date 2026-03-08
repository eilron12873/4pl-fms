<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Inventory Control') }}</h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">{{ __('Company-owned stock only. Custody (customer stock) is managed in the WMS; the FMS receives billing data from WMS via integration.') }}</p>

        <h3 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Own inventory (company stock)') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <a href="{{ route('inventory-valuation.valuation') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-layer-group text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Valuation Report') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Quantity, cost, value') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('inventory-valuation.movements.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-exchange-alt text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Stock Movements') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Receipts, issues, transfers') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('inventory-valuation.adjustments.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-adjust text-2xl text-amber-600 dark:text-amber-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Write-Off & Adjustments') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Adjustments and write-offs') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('inventory-valuation.warehouses.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-warehouse text-2xl text-purple-600 dark:text-purple-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Warehouses') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Locations') }}</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-8">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Total own inventory value') }}</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalValue ?? 0, 2) }}</p>
            @if(isset($report) && $report->isNotEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $report->count() }} {{ __('balance line(s) across warehouses') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>

