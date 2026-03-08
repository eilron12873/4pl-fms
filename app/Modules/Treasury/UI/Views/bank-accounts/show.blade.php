<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $account->name }}</h2>
            <a href="{{ route('treasury.bank-accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to accounts') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bank') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $account->bank_name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Account number') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $account->account_number ?? '-' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Currency') }}</dt><dd class="text-gray-900 dark:text-gray-100">{{ $account->currency }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Balance') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($account->balance, 2) }} {{ $account->currency }}</dd></div>
            </dl>
            @can('treasury.manage')
                <a href="{{ route('treasury.transactions.create', $account->id) }}" class="inline-flex mt-4 px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Record transaction') }}</a>
            @endcan
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Transactions') }}</h3>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Type') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reconciled') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($transactions as $tx)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $tx->transaction_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $tx->description }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $tx->type }}</td>
                            <td class="px-4 py-2 text-right {{ $tx->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($tx->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ $tx->isReconciled() ? __('Yes') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($transactions->hasPages())<div class="mt-2">{{ $transactions->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
