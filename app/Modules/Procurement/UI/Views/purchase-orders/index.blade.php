<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Purchase orders') }}</h2>
            @can('procurement.manage')
            <a href="{{ route('procurement.purchase-orders.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('New P.O.') }}</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('P.O. #') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Vendor') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Order date') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($orders as $o)
                        <tr>
                            <td class="px-4 py-2 font-mono">{{ $o->po_number }}</td>
                            <td class="px-4 py-2">{{ $o->vendor?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $o->order_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($o->total ?? 0, 2) }} {{ $o->currency }}</td>
                            <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs">{{ $o->status }}</span></td>
                            <td class="px-4 py-2"><a href="{{ route('procurement.purchase-orders.show', $o->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No purchase orders yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($orders->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
