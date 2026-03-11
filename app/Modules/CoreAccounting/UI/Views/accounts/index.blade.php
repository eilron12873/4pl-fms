<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Chart of Accounts') }}
            </h2>
            <a href="{{ route('core-accounting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Core Accounting') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('Browse the logistics chart of accounts. Only finance admins can create, edit, deactivate, or bulk-import accounts.') }}
                        </p>
                        @can('core-accounting.manage')
                            <a href="{{ route('core-accounting.accounts.create') }}"
                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600">
                                {{ __('New Account') }}
                            </a>
                        @endcan
                    </div>
                    @can('core-accounting.manage')
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('core-accounting.accounts.import.template') }}"
                               class="inline-flex items-center px-3 py-1.5 border text-sm font-medium rounded-md text-teal-700 bg-white hover:bg-gray-50 dark:bg-gray-900 dark:text-teal-300 dark:border-teal-600">
                                {{ __('Download CSV template') }}
                            </a>
                            <a href="{{ route('core-accounting.accounts.export') }}"
                               class="inline-flex items-center px-3 py-1.5 border text-sm font-medium rounded-md text-teal-700 bg-white hover:bg-gray-50 dark:bg-gray-900 dark:text-teal-300 dark:border-teal-600">
                                {{ __('Export COA CSV') }}
                            </a>
                            <form action="{{ route('core-accounting.accounts.import') }}" method="POST" enctype="multipart/form-data" class="inline-flex items-center gap-2">
                                @csrf
                                <input type="file" name="accounts_csv"
                                       accept=".csv,text/csv"
                                       class="block text-xs text-gray-600 dark:text-gray-300 file:mr-2 file:px-2 file:py-1.5 file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900/40 dark:file:text-teal-200">
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-teal-700 hover:bg-teal-800 dark:bg-teal-600 dark:hover:bg-teal-700">
                                    {{ __('Import CSV') }}
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Code') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Level') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Posting') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($accounts as $account)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-gray-100">{{ $account->code }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $account->name }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 capitalize">{{ $account->type }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $account->level }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        @if($account->is_posting)
                                            <span class="text-green-600 dark:text-green-400">{{ __('Yes') }}</span>
                                        @else
                                            <span class="text-gray-400">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if($account->is_active ?? true)
                                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('Active') }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm space-x-3">
                                        <a href="{{ route('core-accounting.accounts.show', $account->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">{{ __('View') }}</a>
                                        @can('core-accounting.manage')
                                            <a href="{{ route('core-accounting.accounts.edit', $account->id) }}" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">
                                                {{ __('Edit') }}
                                            </a>
                                            @if($account->is_active ?? true)
                                                <form action="{{ route('core-accounting.accounts.deactivate', $account->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm"
                                                            onclick="return confirm('{{ __('Deactivate this account? It will no longer be used for new journals.') }}')">
                                                        {{ __('Deactivate') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No accounts found. Run ChartOfAccountsSeeder.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($accounts->hasPages())
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                        {{ $accounts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
