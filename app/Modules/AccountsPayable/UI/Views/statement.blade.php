<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Statement of Account') }}</h2></x-slot>
    <div class="py-4 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('accounts-payable.statement') }}" class="flex flex-wrap gap-4 items-end">
                <div><label for="vendor_id" class="block text-sm font-medium mb-1">{{ __('Vendor') }} *</label>
                    <select id="vendor_id" name="vendor_id" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        @foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->code }} - {{ $v->name }}</option>@endforeach
                    </select></div>
                <div><label for="from_date" class="block text-sm font-medium mb-1">{{ __('From date') }}</label><input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></div>
                <div><label for="to_date" class="block text-sm font-medium mb-1">{{ __('To date') }}</label><input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm">{{ __('View') }}</button>
            </form>
        </div>
        @if(isset($vendor))
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $vendor->name }} ({{ $vendor->code }})</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __('Outstanding balance') }}: <strong>{{ number_format($balance ?? 0, 2) }} {{ $vendor->currency }}</strong></p>
                <h4 class="text-sm font-medium mt-4">{{ __('Bills') }}</h4>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm mt-2">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-4 py-2 text-left font-semibold">{{ __('Bill #') }}</th><th class="px-4 py-2 text-left">{{ __('Date') }}</th><th class="px-4 py-2 text-right">{{ __('Total') }}</th><th class="px-4 py-2 text-right">{{ __('Balance') }}</th><th class="px-4 py-2">{{ __('Status') }}</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($bills ?? [] as $b)
                            <tr><td class="px-4 py-2"><a href="{{ route('accounts-payable.bills.show', $b->id) }}" class="text-blue-600 hover:underline font-mono">{{ $b->bill_number }}</a></td><td class="px-4 py-2">{{ $b->bill_date?->format('Y-m-d') }}</td><td class="px-4 py-2 text-right">{{ number_format($b->total, 2) }}</td><td class="px-4 py-2 text-right">{{ number_format($b->total - $b->amount_allocated, 2) }}</td><td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs">{{ $b->status }}</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
                <h4 class="text-sm font-medium mt-4">{{ __('Payments') }}</h4>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm mt-2">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-4 py-2 text-left">{{ __('Date') }}</th><th class="px-4 py-2 text-right">{{ __('Amount') }}</th><th class="px-4 py-2 text-left">{{ __('Reference') }}</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($payments ?? [] as $pmt)
                            <tr><td class="px-4 py-2">{{ $pmt->payment_date?->format('Y-m-d') }}</td><td class="px-4 py-2 text-right">{{ number_format($pmt->amount, 2) }} {{ $pmt->currency }}</td><td class="px-4 py-2">{{ $pmt->reference ?? '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
