<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Bank Accounts') }}</h2>
            @can('treasury.manage')
                <a href="{{ route('treasury.bank-accounts.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Add account') }}</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Bank / Account #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Currency') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Balance') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($accounts as $account)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $account->name }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $account->bank_name }} {{ $account->account_number ? ' / ' . $account->account_number : '' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $account->currency }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($account->balance, 2) }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs {{ $account->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $account->is_active ? __('Active') : __('Inactive') }}</span></td>
                                <td class="px-4 py-2"><a href="{{ route('treasury.bank-accounts.show', $account->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No bank accounts yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $accounts->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
