<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Approval Workflows') }}</h2>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Administration') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Pending approvals') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Vendor bills (pending approval)') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $pending_vendor_bills ?? 0 }}</p>
                    <a href="{{ route('lfs-administration.approval-workflows.vendor-bills') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">{{ __('View queue') }}</a>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('All other areas') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">—</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ __('Waiting for domain pending states + gating integration.') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

