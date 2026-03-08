<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Statement of Account') }}</h2>
            <a href="{{ route('accounts-receivable.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to AR') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('accounts-receivable.statement') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Client') }} *</label>
                    <select id="client_id" name="client_id" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected(($client->id ?? null) == $c->id)>{{ $c->code }} - {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                    <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                    <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('View') }}</button>
            </form>
        </div>
        @if(isset($client))
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $client->name }} ({{ $client->code }})</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Outstanding balance') }}: <strong>{{ number_format($balance ?? 0, 2) }} {{ $client->currency }}</strong></p>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-4">{{ __('Invoices') }}</h4>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm mt-2">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Invoice #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Allocated') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Balance') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoices ?? [] as $inv)
                            <tr>
                                <td class="px-4 py-2"><a href="{{ route('accounts-receivable.invoices.show', $inv->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-mono">{{ $inv->invoice_number }}</a></td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $inv->invoice_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($inv->total, 2) }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ number_format($inv->amount_allocated, 2) }}</td>
                                <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($inv->total - $inv->amount_allocated, 2) }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs">{{ $inv->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-4">{{ __('Payments') }}</h4>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm mt-2">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($payments ?? [] as $pmt)
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $pmt->payment_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($pmt->amount, 2) }} {{ $pmt->currency }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $pmt->reference ?? '&mdash;' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
