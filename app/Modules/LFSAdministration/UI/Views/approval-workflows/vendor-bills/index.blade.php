<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Vendor Bill Approval Queue') }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Approval Workflows') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('lfs-administration.approval-workflows.vendor-bills') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Vendor ID') }}</label>
                    <input type="number" id="vendor_id" name="vendor_id" value="{{ $filters['vendor_id'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-32">
                </div>
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Filter') }}</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Bill #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Bill date') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Due date') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($bills as $bill)
                            <tr>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $bill->vendor?->code ?? '' }} - {{ $bill->vendor?->name ?? '' }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                    <a href="{{ route('lfs-administration.approval-workflows.vendor-bills.show', $bill->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $bill->bill_number }}</a>
                                </td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $bill->bill_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format((float) ($bill->total ?? 0), 2) }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $bill->due_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('lfs-administration.approval-workflows.vendor-bills.approve', $bill->id) }}" class="inline-block mr-2">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">{{ __('Approve') }}</button>
                                    </form>

                                    <form method="POST" action="{{ route('lfs-administration.approval-workflows.vendor-bills.reject', $bill->id) }}" class="inline-block">
                                        @csrf
                                        <div class="hidden">
                                            <input type="text" name="comments" value="" />
                                        </div>
                                        <button type="submit" class="inline-flex items-center px-3 py-1 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">{{ __('Reject') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No pending vendor bills for the selected filters.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($bills->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                    {{ $bills->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

