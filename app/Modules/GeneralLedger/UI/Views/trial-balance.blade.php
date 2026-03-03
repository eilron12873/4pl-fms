<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trial Balance') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">{{ __('Account Code') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">{{ __('Account Name') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">{{ __('Debit') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">{{ __('Credit') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">{{ __('Net Balance') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp
                    @forelse ($rows as $row)
                        @php
                            $totalDebit += $row['debit'];
                            $totalCredit += $row['credit'];
                        @endphp
                        <tr>
                            <td class="px-4 py-2 text-gray-800">{{ $row['account']->code }}</td>
                            <td class="px-4 py-2 text-gray-800">{{ $row['account']->name }}</td>
                            <td class="px-4 py-2 text-right text-gray-800">{{ number_format($row['debit'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-800">{{ number_format($row['credit'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-800">{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">
                                {{ __('No journal data available yet.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                    <tr>
                        <th colspan="2" class="px-4 py-2 text-right font-semibold text-gray-900">{{ __('Totals') }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-900">{{ number_format($totalDebit, 2) }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-900">{{ number_format($totalCredit, 2) }}</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-900">{{ number_format($totalDebit - $totalCredit, 2) }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

