<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Allocation Engine') }}</h2>
            <a href="{{ route('costing-engine.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Costing') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <p class="text-gray-600 dark:text-gray-400">{{ __('The Allocation Engine will allow you to define rules for allocating shared costs (e.g. overhead, depreciation, warehouse space) to clients, shipments, routes, warehouses, or projects.') }}</p>
            <p class="mt-4 text-gray-500 dark:text-gray-500 text-sm">{{ __('This feature is planned for a future release. For now, ensure journal entries post with the appropriate dimensions (client_id, shipment_id, route_id, warehouse_id, project_id) so that profitability reports reflect allocated amounts.') }}</p>
        </div>
    </div>
</x-app-layout>
