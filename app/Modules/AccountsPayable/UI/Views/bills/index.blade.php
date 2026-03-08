<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Vendor Bills') }}</h2>
            <div class="flex gap-2 items-center">
                @can('accounts-payable.manage')
                <a href="{{ route('accounts-payable.bills.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create bill') }}</a>
                @endcan
                <a href="{{ route('accounts-payable.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to AP') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('accounts-payable.bills.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Vendor') }}</label>
                    <select id="vendor_id" name="vendor_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->code }} - {{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                    <select id="status" name="status" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="draft" @selected(request('status') === 'draft')>{{ __('Draft') }}</option>
                        <option value="issued" @selected(request('status') === 'issued')>{{ __('Issued') }}</option>
                        <option value="partially_paid" @selected(request('status') === 'partially_paid')>{{ __('Partially paid') }}</option>
                        <option value="paid" @selected(request('status') === 'paid')>{{ __('Paid') }}</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Bill #') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($bills as $b)
                            <tr>
                                <td class="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $b->bill_number }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $b->vendor->code ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $b->bill_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($b->total, 2) }} {{ $b->currency }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs">{{ $b->status }}</span></td>
                                <td class="px-4 py-2"><a href="{{ route('accounts-payable.bills.show', $b->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No bills yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bills->hasPages())<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $bills->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
