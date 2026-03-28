@php
    $fmtAddr = static function ($l1, $l2, $city, $region, $postal, $country): string {
        $parts = array_filter([$l1, $l2, trim(implode(' ', array_filter([$city, $region, $postal]))), $country]);

        return $parts !== [] ? implode(', ', $parts) : '—';
    };
    $billAddr = $fmtAddr($client->bill_address_line1, $client->bill_address_line2, $client->bill_city, $client->bill_region, $client->bill_postal_code, $client->bill_country);
    $shipAddr = $client->ship_same_as_bill
        ? __('Same as bill-to')
        : $fmtAddr($client->ship_address_line1, $client->ship_address_line2, $client->ship_city, $client->ship_region, $client->ship_postal_code, $client->ship_country);
    $deliveryLabel = match ($client->invoice_delivery_method) {
        'email' => __('Email'),
        'portal' => __('Customer portal'),
        'edi' => __('EDI'),
        'mail' => __('Postal mail'),
        'other' => __('Other'),
        default => null,
    };
    $payMethodLabel = match ($client->customer_payment_method) {
        'ach' => __('ACH / bank transfer'),
        'wire' => __('Wire'),
        'check' => __('Check'),
        'card' => __('Card'),
        'other' => __('Other'),
        default => null,
    };
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Client') }}: {{ $client->code }}</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('accounts-receivable.clients.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Back to list') }}</a>
                @canany(['accounts-receivable.manage', 'accounts-receivable.clients.manage'])
                    <a href="{{ route('accounts-receivable.clients.edit', $client) }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('accounts-receivable.clients.toggle-active', $client) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-md bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">{{ $client->is_active ? __('Deactivate') : __('Activate') }}</button>
                    </form>
                    @if($client->ar_invoices_count === 0 && $client->ar_payments_count === 0 && $client->contracts_count === 0)
                        <form method="POST" action="{{ route('accounts-receivable.clients.destroy', $client) }}" class="inline" onsubmit="return confirm(@json(__('Delete this client? This cannot be undone.')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">{{ __('Delete') }}</button>
                        </form>
                    @endif
                @endcanany
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>@endif

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden p-6 space-y-6 text-sm">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Identity') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Code') }}</dt><dd class="font-mono font-medium text-gray-900 dark:text-gray-100">{{ $client->code }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd><span class="px-2 py-0.5 rounded text-xs {{ $client->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $client->is_active ? __('Active') : __('Inactive') }}</span></dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('Display name') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ $client->display_name }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Legal name') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->legal_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Trading name') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->trading_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('External / ERP ID') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->external_id ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Tax ID') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->tax_id ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Invoice currency') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->currency }}</dd></div>
                </dl>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Credit & terms') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Payment terms') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->payment_terms_days !== null ? __(':days days', ['days' => $client->payment_terms_days]) : '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Credit limit') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->credit_limit !== null ? number_format((float) $client->credit_limit, 2).' '.$client->currency : '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Credit hold') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->credit_hold ? __('Yes') : __('No') }}</dd></div>
                </dl>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Addresses') }}</h3>
                <dl class="space-y-2">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bill-to') }}</dt><dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $billAddr }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Ship-to') }}</dt><dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $shipAddr }}</dd></div>
                </dl>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Invoicing & contact') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Contact') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->invoice_contact_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->invoice_contact_email ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->invoice_contact_phone ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Invoice delivery') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $deliveryLabel ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Typical payment from customer') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $payMethodLabel ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('PO required on invoices') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $client->po_number_required ? __('Yes') : __('No') }}</dd></div>
                </dl>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Accounting') }}</h3>
                <dl><div><dt class="text-gray-500 dark:text-gray-400">{{ __('Default revenue account') }}</dt><dd class="font-mono text-gray-900 dark:text-gray-100">{{ $client->default_revenue_account_code ?? '—' }}</dd></div></dl>
            </div>

            @if($client->internal_notes)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Internal notes') }}</h3>
                    <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $client->internal_notes }}</p>
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('Related records') }}</h3>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <li>{{ __('Invoices') }}: <strong class="text-gray-900 dark:text-gray-100">{{ $client->ar_invoices_count }}</strong></li>
                <li>{{ __('Payments') }}: <strong class="text-gray-900 dark:text-gray-100">{{ $client->ar_payments_count }}</strong></li>
                <li>{{ __('Billing contracts') }}: <strong class="text-gray-900 dark:text-gray-100">{{ $client->contracts_count }}</strong></li>
            </ul>
            @if($client->ar_invoices_count > 0 || $client->ar_payments_count > 0 || $client->contracts_count > 0)
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('This client cannot be deleted until related invoices, payments, and contracts are removed. You can deactivate the client instead.') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
