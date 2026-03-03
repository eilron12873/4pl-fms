<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('General Ledger') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" action="{{ route('general-ledger.ledger') }}" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div>
                        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Account') }}
                        </label>
                        <select id="account_id" name="account_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected($account && $account->id === $a->id)>
                                    {{ $a->code }} — {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                            {{ __('Apply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                @if (! $account)
                    <p class="text-sm text-gray-500">
                        {{ __('No accounts available yet. Post at least one journal entry to see ledger data.') }}
                    </p>
                @else
                    <h3 class="font-semibold text-lg mb-2">
                        {{ $account->code }} — {{ $account->name }}
                    </h3>

                    <table class="min-w-full divide-y divide-gray-200 text-sm mt-4">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">{{ __('Journal #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">{{ __('Description') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700">{{ __('Debit') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700">{{ __('Credit') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700">{{ __('Balance') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @php $lastBalance = null; @endphp
                        @forelse ($lines as $line)
                            @php $lastBalance = $line['balance']; @endphp
                            <tr>
                                <td class="px-4 py-2 text-gray-800">
                                    {{ \Illuminate\Support\Carbon::parse($line['date'])->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-2 text-gray-800">
                                    {{ $line['journal_number'] }}
                                </td>
                                <td class="px-4 py-2 text-gray-800">
                                    {{ $line['description'] }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-800">
                                    {{ number_format($line['debit'], 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-800">
                                    {{ number_format($line['credit'], 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-800">
                                    {{ number_format($line['balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                    {{ __('No ledger entries for this account yet.') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        @if ($account && $lines->isNotEmpty())
                            <tfoot class="bg-gray-50">
                            <tr>
                                <th colspan="5" class="px-4 py-2 text-right font-semibold text-gray-900">
                                    {{ __('Ending Balance') }}
                                </th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-900">
                                    {{ number_format($lastBalance, 2) }}
                                </th>
                            </tr>
                            </tfoot>
                        @endif
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

