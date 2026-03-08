<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('General Ledger') }}
            </h2>
            <a href="{{ route('general-ledger.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to General Ledger') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('general-ledger.ledger') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Account') }}</label>
                        <select id="account_id" name="account_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected($account && $account->id === $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period') }}</label>
                        <select id="period" name="period" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach($periods ?? [] as $p)
                                <option value="{{ $p->code }}" @selected($from_date && $to_date && $from_date === $p->start_date?->toDateString())>{{ $p->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From date') }}</label>
                        <input type="date" id="from_date" name="from_date" value="{{ $from_date ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    </div>
                    <div>
                        <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To date') }}</label>
                        <input type="date" id="to_date" name="to_date" value="{{ $to_date ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Apply') }}</button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-4">
                <div class="p-4">
                    @if (! $account)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No accounts available yet. Post at least one journal entry to see ledger data.') }}</p>
                    @else
                        <h3 class="font-semibold text-lg mb-2 dark:text-gray-100">{{ $account->code }} — {{ $account->name }}</h3>
                        @if(isset($opening_balance) && $opening_balance != 0)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ __('Opening balance') }}: {{ number_format($opening_balance, 2) }}</p>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Journal #') }}</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Debit') }}</th>
                                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Credit') }}</th>
                                        <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                    @forelse ($lines as $line)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $line['date'] ? \Illuminate\Support\Carbon::parse($line['date'])->format('Y-m-d') : '—' }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                                @if(!empty($line['journal_id']))
                                                    <a href="{{ route('core-accounting.journals.show', $line['journal_id']) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-mono">{{ $line['journal_number'] ?? '—' }}</a>
                                                @else
                                                    {{ $line['journal_number'] ?? '—' }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $line['description'] ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($line['debit'], 2) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($line['credit'], 2) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format($line['balance'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">{{ __('No ledger entries for this account yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(isset($paginator) && $paginator && $paginator->hasPages())
                            <div class="mt-4">
                                {{ $paginator->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
