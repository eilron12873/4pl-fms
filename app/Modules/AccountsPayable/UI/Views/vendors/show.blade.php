<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Vendor') }}: {{ $vendor->code }}</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('accounts-payable.vendors.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Back to list') }}</a>
                @can('accounts-payable.manage')
                    <a href="{{ route('accounts-payable.vendors.edit', $vendor) }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Edit') }}</a>
                    @if($vendor->bills_count === 0 && $vendor->payments_count === 0 && $vendor->purchase_orders_count === 0)
                        <form method="POST" action="{{ route('accounts-payable.vendors.destroy', $vendor) }}" class="inline" onsubmit="return confirm(@json(__('Delete this vendor? This cannot be undone.')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">{{ __('Delete') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>@endif

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Code') }}</dt>
                    <dd class="font-mono font-medium text-gray-900 dark:text-gray-100">{{ $vendor->code }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                    <dd><span class="px-2 py-0.5 rounded text-xs {{ $vendor->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $vendor->is_active ? __('Active') : __('Inactive') }}</span></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Name') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $vendor->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Category') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $vendor->category ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Tax ID') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $vendor->tax_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Currency') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $vendor->currency }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Payment terms (days)') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $vendor->payment_terms_days }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Preferred payment') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">
                        @if($vendor->preferred_payment_method === 'ach')
                            {{ __('ACH / Bank transfer') }}
                        @elseif($vendor->preferred_payment_method === 'check')
                            {{ __('Check') }}
                        @elseif($vendor->preferred_payment_method === 'other')
                            {{ __('Other') }}
                        @else
                            {{ __('Not specified') }}
                        @endif
                    </dd>
                </div>
                @if($vendor->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Notes') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $vendor->notes }}</dd>
                    </div>
                @endif
            </div>
            @if($vendor->bank_name || $vendor->bank_account_number || $vendor->bank_swift_code)
                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900/40">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __('Bank details') }}</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bank name') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $vendor->bank_name ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Account number') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $vendor->bank_account_number ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('SWIFT / BIC') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $vendor->bank_swift_code ?? '—' }}</dd></div>
                    </dl>
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('Related records') }}</h3>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <li>{{ __('Bills') }}: <strong class="text-gray-900 dark:text-gray-100">{{ $vendor->bills_count }}</strong></li>
                <li>{{ __('Payments') }}: <strong class="text-gray-900 dark:text-gray-100">{{ $vendor->payments_count }}</strong></li>
                <li>{{ __('Purchase orders') }}: <strong class="text-gray-900 dark:text-gray-100">{{ $vendor->purchase_orders_count }}</strong></li>
            </ul>
            @if($vendor->bills_count > 0 || $vendor->payments_count > 0 || $vendor->purchase_orders_count > 0)
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('This vendor cannot be deleted until related bills, payments, and purchase orders are removed or reassigned.') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
