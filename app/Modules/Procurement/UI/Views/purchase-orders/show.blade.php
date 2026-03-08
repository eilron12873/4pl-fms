<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('P.O.') }} {{ $order->po_number }}</h2>
            <a href="{{ route('procurement.purchase-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to P.O. list') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm mb-4">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('P.O. number') }}</dt><dd class="font-mono font-medium">{{ $order->po_number }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Vendor') }}</dt><dd>{{ $order->vendor?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Order date') }}</dt><dd>{{ $order->order_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Expected date') }}</dt><dd>{{ $order->expected_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd><span class="px-2 py-0.5 rounded text-xs">{{ $order->status }}</span></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Total') }}</dt><dd class="font-medium">{{ number_format($order->total ?? 0, 2) }} {{ $order->currency }}</dd></div>
                @if($order->received_date)<div><dt class="text-gray-500 dark:text-gray-400">{{ __('Received date') }}</dt><dd>{{ $order->received_date?->format('Y-m-d') }}</dd></div>@endif
            </dl>
            @if(auth()->user()?->can('procurement.manage'))
                <div class="flex flex-wrap gap-2 mt-2 mb-4">
                    @if($order->status === 'draft')
                        <form method="POST" action="{{ route('procurement.purchase-orders.issue', $order->id) }}">
                            @csrf
                            <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Issue P.O.') }}</button>
                        </form>
                    @endif
                    @if($order->status === 'issued')
                        <form method="POST" action="{{ route('procurement.purchase-orders.receive', $order->id) }}">
                            @csrf
                            <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-green-600 text-white text-sm hover:bg-green-700">{{ __('Mark received') }}</button>
                        </form>
                    @endif
                    @if(in_array($order->status, ['issued', 'received']) && auth()->user()?->can('accounts-payable.manage'))
                        <a href="{{ route('accounts-payable.bills.create', ['purchase_order_id' => $order->id]) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Create bill from P.O.') }}</a>
                    @endif
                </div>
            @endif
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-2">{{ __('Lines') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Description') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Qty') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Unit price') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Account') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->lines as $line)
                        <tr>
                            <td class="px-4 py-2">{{ $line->description }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($line->quantity, 4) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($line->unit_price, 4) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($line->amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ $line->account_code ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
