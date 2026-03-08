<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Financial Reporting (Advanced)') }}</h2>
    </x-slot>

    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <p class="mb-6 text-gray-600 dark:text-gray-400">{{ __('Advanced financial reports: management P&L by dimension, cash flow analysis (Treasury + GL), and AR/AP KPI dashboards.') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('financial-reporting.management-reports') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-chart-line"></i></span>
                    {{ __('Management Reports') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Income statement with YTD net income and gross margin %.') }}</p>
            </a>
            <a href="{{ route('financial-reporting.comparative-income-statement') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-columns"></i></span>
                    {{ __('Comparative Income Statement') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Current period vs prior period with variance.') }}</p>
            </a>
            <a href="{{ route('financial-reporting.tax-summary') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-receipt"></i></span>
                    {{ __('Tax Summary') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Revenue and expense summary for tax reporting.') }}</p>
            </a>
            <a href="{{ route('financial-reporting.management-pl-dimension') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-layer-group"></i></span>
                    {{ __('Management P&L by dimension') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Income statement by client, warehouse, or project.') }}</p>
            </a>
            <a href="{{ route('financial-reporting.cash-flow-analysis') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-money-bill-wave"></i></span>
                    {{ __('Cash flow analysis') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('GL cash flow and Treasury cash position side by side.') }}</p>
            </a>
            <a href="{{ route('financial-reporting.kpi-dashboard') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-tachometer-alt"></i></span>
                    {{ __('AR/AP KPI dashboard') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Aging, DSO, and margin variance at a glance.') }}</p>
            </a>
        </div>
    </div>
</x-app-layout>

