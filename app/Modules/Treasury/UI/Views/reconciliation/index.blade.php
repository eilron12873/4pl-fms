<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Bank Reconciliation') }}</h2>
            <a href="{{ route('treasury.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Treasury') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('treasury.reconciliation.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Bank account') }} *</label>
                    <select id="bank_account_id" name="bank_account_id" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('Select account') }}</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected(isset($account) && $account->id == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('View') }}</button>
            </form>
        </div>
        @if(isset($account))
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $account->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Book balance') }}: {{ number_format($account->balance, 2) }} {{ $account->currency }}</p>

                @can('treasury.manage')
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Add statement line') }}</h4>
                        <form method="POST" action="{{ route('treasury.reconciliation.statement-lines.store') }}" class="flex flex-wrap gap-4 items-end">
                            @csrf
                            <input type="hidden" name="bank_account_id" value="{{ $account->id }}">
                            <div><label for="statement_date" class="block text-sm mb-1">{{ __('Date') }} *</label><input type="date" id="statement_date" name="statement_date" value="{{ now()->toDateString() }}" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"></div>
                            <div><label for="amount" class="block text-sm mb-1">{{ __('Amount') }} *</label><input type="number" id="amount" name="amount" step="0.01" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm w-32"></div>
                            <div><label for="description" class="block text-sm mb-1">{{ __('Description') }}</label><input type="text" id="description" name="description" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm w-48"></div>
                            <div><label for="reference" class="block text-sm mb-1">{{ __('Reference') }}</label><input type="text" id="reference" name="reference" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm w-32"></div>
                            <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm">{{ __('Add line') }}</button>
                        </form>
                    </div>
                @endcan

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('Unmatched statement lines') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300 w-24">{{ __('Date') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                        <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 w-24">{{ __('Amount') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300 w-56">{{ __('Match to') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                                    @forelse($unmatchedStatementLines as $line)
                                        <tr>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $line->statement_date?->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ Str::limit($line->description ?? '-', 40) }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($line->amount, 2) }}</td>
                                            <td class="px-3 py-2">
                                                @if($unreconciledTransactions->isNotEmpty())
                                                    <form method="POST" action="{{ route('treasury.reconciliation.match') }}" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                                        @csrf
                                                        <input type="hidden" name="statement_line_id" value="{{ $line->id }}">
                                                        <select name="bank_transaction_id" class="block w-full sm:w-auto min-w-0 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm" required>
                                                            <option value="">{{ __('— Select transaction —') }}</option>
                                                            @foreach($unreconciledTransactions as $tx)
                                                                <option value="{{ $tx->id }}">{{ $tx->transaction_date?->format('Y-m-d') }} · {{ number_format($tx->amount, 2) }} · {{ Str::limit($tx->description, 25) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="shrink-0 px-3 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">{{ __('Match') }}</button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 text-xs">{{ __('No transactions to match') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">{{ __('No unmatched statement lines.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('Unreconciled transactions') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600 text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300 w-24">{{ __('Date') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                        <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 w-28">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                                    @forelse($unreconciledTransactions as $tx)
                                        <tr>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx->transaction_date?->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ Str::limit($tx->description, 50) }}</td>
                                            <td class="px-3 py-2 text-right font-medium {{ $tx->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($tx->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">{{ __('No unreconciled transactions.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
