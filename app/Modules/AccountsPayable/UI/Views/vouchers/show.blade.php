<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Voucher') }} {{ $voucher->voucher_number }}</h2>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Print') }}</button>
                <a href="{{ route('accounts-payable.vouchers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Vouchers') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-3xl mx-auto sm:px-6 lg:px-8 print:max-w-none">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 print:shadow-none">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Payment voucher') }}</h3>
            <dl class="grid grid-cols-1 gap-2 text-sm mb-4">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Voucher number') }}</dt><dd class="font-mono font-medium">{{ $voucher->voucher_number }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt><dd>{{ $voucher->voucher_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Vendor') }}</dt><dd>{{ $voucher->payment?->vendor?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Amount') }}</dt><dd class="font-medium">{{ number_format($voucher->payment?->amount ?? 0, 2) }} {{ $voucher->payment?->currency ?? '' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Reference') }}</dt><dd>{{ $voucher->payment?->reference ?? '—' }}</dd></div>
            </dl>
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-2">{{ __('Allocations to bills') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Bill #') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($voucher->payment?->billPayments ?? [] as $bp)
                        <tr>
                            <td class="px-4 py-2">{{ $bp->bill?->bill_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($bp->amount ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    @if(($voucher->payment?->billPayments?->count() ?? 0) === 0)
                        <tr><td colspan="2" class="px-4 py-2 text-gray-500">{{ __('No allocations') }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
