<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Treasury & Cash') }}</h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <a href="{{ route('treasury.bank-accounts.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-university text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Bank Accounts') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage bank accounts') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('treasury.reconciliation.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-balance-scale text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Bank Reconciliation') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Match statement lines') }}</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Cash position') }}</h3>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Account') }}</th>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Bank / Number') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Balance') }}</th>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($accounts as $account)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $account->name }}</td>
                            <td class="px-4 py-2">{{ $account->bank_name }} {{ $account->account_number ? ' / ' . $account->account_number : '' }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($account->balance, 2) }} {{ $account->currency }}</td>
                            <td class="px-4 py-2"><a href="{{ route('treasury.bank-accounts.show', $account->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No bank accounts.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(isset($total_by_currency) && count($total_by_currency) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    @foreach($total_by_currency as $currency => $total)
                        <p class="text-sm">{{ __('Total') }} ({{ $currency }}): <strong>{{ number_format($total, 2) }}</strong></p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
