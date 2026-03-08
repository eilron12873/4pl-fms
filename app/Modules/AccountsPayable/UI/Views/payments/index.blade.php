<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Payments') }}</h2>
            @can('accounts-payable.manage')
                <a href="{{ route('accounts-payable.payments.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Record payment') }}</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('accounts-payable.payments.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Vendor') }}</label>
                    <select id="vendor_id" name="vendor_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->code }} - {{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($payments as $pmt)
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $pmt->payment_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $pmt->vendor->code ?? '-' }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($pmt->amount, 2) }} {{ $pmt->currency }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $pmt->reference ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No payments yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $payments->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
