<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Costing & Profitability') }}</h2>
    </x-slot>

    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <p class="mb-6 text-gray-600 dark:text-gray-400">{{ __('View profitability by client, shipment, route, warehouse, or project. Revenue and cost are derived from AR invoices and posted journal lines.') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('costing-engine.client-profitability') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-user-tie"></i></span>
                    {{ __('Client Profitability') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Revenue vs cost by client from invoices and GL.') }}</p>
            </a>
            <a href="{{ route('costing-engine.shipment-profitability') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-boxes"></i></span>
                    {{ __('Shipment Profitability') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Revenue and cost by shipment (GL dimensions).') }}</p>
            </a>
            <a href="{{ route('costing-engine.route-profitability') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-route"></i></span>
                    {{ __('Route Profitability') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Revenue and cost by route (GL dimensions).') }}</p>
            </a>
            <a href="{{ route('costing-engine.warehouse-profitability') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-warehouse"></i></span>
                    {{ __('Warehouse Profitability') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Revenue and cost by warehouse from GL.') }}</p>
            </a>
            <a href="{{ route('costing-engine.project-profitability') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-project-diagram"></i></span>
                    {{ __('Project Profitability') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Revenue and cost by project (GL dimensions).') }}</p>
            </a>
            <a href="{{ route('costing-engine.allocation-engine') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><i class="fas fa-sliders-h"></i></span>
                    {{ __('Allocation Engine') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure cost allocation rules (future).') }}</p>
            </a>
        </div>
    </div>
</x-app-layout>

