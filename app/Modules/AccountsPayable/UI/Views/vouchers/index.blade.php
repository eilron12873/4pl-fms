<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Payment vouchers') }}</h2>
            <a href="{{ route('accounts-payable.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to AP') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('accounts-payable.vouchers.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Voucher #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($vouchers as $v)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $v->voucher_number }}</td>
                                <td class="px-4 py-2">{{ $v->voucher_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">{{ $v->payment?->vendor?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($v->payment?->amount ?? 0, 2) }} {{ $v->payment?->currency ?? '' }}</td>
                                <td class="px-4 py-2"><a href="{{ route('accounts-payable.vouchers.show', $v->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View / Print') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No vouchers yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vouchers->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $vouchers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
