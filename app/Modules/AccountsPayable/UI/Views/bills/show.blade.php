<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Bill') }} {{ $bill->bill_number }}</h2>
            <a href="{{ route('accounts-payable.bills.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Bills') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Vendor') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bill->vendor->code }} - {{ $bill->vendor->name }}</dd></div>
                @if($bill->purchaseOrder)<div><dt class="text-gray-500 dark:text-gray-400">{{ __('P.O.') }}</dt><dd><a href="{{ route('procurement.purchase-orders.show', $bill->purchase_order_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $bill->purchaseOrder->po_number }}</a></dd></div>@endif
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bill date') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $bill->bill_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Due date') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $bill->due_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd><span class="px-2 py-0.5 rounded text-xs">{{ $bill->status }}</span></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Total') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($bill->total, 2) }} {{ $bill->currency }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Balance due') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($bill->balance_due, 2) }} {{ $bill->currency }}</dd></div>
            </dl>
            @if(!$bill->isIssued() && auth()->user()?->can('accounts-payable.manage'))
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('accounts-payable.bills.edit', $bill->id) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Edit bill') }}</a>
                    <form method="POST" action="{{ route('accounts-payable.bills.issue', $bill->id) }}">
                        @csrf
                        <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Issue bill') }}</button>
                    </form>
                </div>
            @endif
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Lines') }}</h3>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Qty') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Unit price') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Journal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($bill->lines as $line)
                        <tr>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $line->description }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ number_format($line->quantity, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ number_format($line->unit_price, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($line->amount, 2) }}</td>
                            <td class="px-4 py-2">@if($line->journal_id)<a href="{{ route('core-accounting.journals.show', $line->journal_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-mono">{{ $line->journal->journal_number ?? $line->journal_id }}</a>@else &mdash; @endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($bill->adjustments->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Adjustments') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Number') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reason') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($bill->adjustments as $adj)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $adj->type }}</td>
                                <td class="px-4 py-2 font-mono text-gray-600 dark:text-gray-300">{{ $adj->adjustment_number }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($adj->amount, 2) }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $adj->reason ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($bill->isIssued() && $bill->balance_due > 0 && auth()->user()?->can('accounts-payable.manage'))
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Vendor credit note') }}</h3>
                <form method="POST" action="{{ route('accounts-payable.bills.credit-note', $bill->id) }}" class="flex flex-wrap gap-4 items-end">
                    @csrf
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Amount') }}</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="{{ $bill->balance_due }}" value="{{ $bill->balance_due }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-32">
                    </div>
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Reason') }}</label>
                        <input type="text" id="reason" name="reason" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-64">
                    </div>
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-amber-600 text-white text-sm hover:bg-amber-700">{{ __('Create credit note') }}</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
