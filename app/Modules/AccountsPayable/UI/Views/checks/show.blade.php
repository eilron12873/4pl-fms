<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Check') }} #{{ $check->check_number }}</h2>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Print') }}</button>
                <a href="{{ route('accounts-payable.checks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Check register') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-3xl mx-auto sm:px-6 lg:px-8 print:max-w-none">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 print:shadow-none">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Check') }}</h3>
            <dl class="grid grid-cols-1 gap-2 text-sm mb-4">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Check number') }}</dt><dd class="font-mono font-medium">{{ $check->check_number }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt><dd>{{ $check->check_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Payee') }}</dt><dd class="font-medium">{{ $check->payee }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Amount') }}</dt><dd class="font-medium">{{ number_format($check->amount ?? 0, 2) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Amount (words)') }}</dt><dd class="font-medium">{{ $amountInWords ?? number_format($check->amount ?? 0, 2) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bank account') }}</dt><dd>{{ $check->bankAccount?->name ?? '—' }} @if($check->bankAccount)({{ $check->bankAccount->account_number }})@endif</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd><span class="px-2 py-0.5 rounded text-xs {{ $check->status === 'void' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ $check->status }}</span></dd></div>
            </dl>
            @if($check->status !== 'void' && auth()->user()?->can('accounts-payable.manage'))
                <form method="POST" action="{{ route('accounts-payable.checks.void', $check->id) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-red-600 text-white text-sm hover:bg-red-700">{{ __('Void check') }}</button>
                </form>
            @endif
            <p class="text-xs text-gray-500 mt-4">{{ __('Payment ID') }}: {{ $check->payment_id }}</p>
        </div>
    </div>
</x-app-layout>
