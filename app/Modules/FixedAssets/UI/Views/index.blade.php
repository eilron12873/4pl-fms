<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Fixed Assets') }}</h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <a href="{{ route('fixed-assets.assets.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-clipboard-list text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Asset Registry') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Master list and register') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('fixed-assets.depreciation.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-hourglass-half text-2xl text-amber-600 dark:text-amber-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Depreciation') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Schedule, history, run') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('fixed-assets.maintenance.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-tools text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Maintenance Cost Tracking') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Record maintenance costs') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('fixed-assets.reports.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-chart-bar text-2xl text-indigo-600 dark:text-indigo-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Reports') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Cost, profitability, utilization') }}</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Summary (active assets)') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Count') }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $activeCount ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Total cost') }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalCost ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Accumulated depreciation') }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalAccumDepn ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Net book value') }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($bookValue ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
