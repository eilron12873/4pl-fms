<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Procurement') }}</h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('procurement.purchase-requests.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600"><i class="fas fa-clipboard-list"></i></span>
                    {{ __('Purchase requests') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Create and view purchase requests (P.R.)') }}</p>
            </a>
            <a href="{{ route('procurement.purchase-orders.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600"><i class="fas fa-file-purchase-order"></i></span>
                    {{ __('Purchase orders') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Create and view purchase orders (P.O.)') }}</p>
            </a>
        </div>
    </div>
</x-app-layout>
