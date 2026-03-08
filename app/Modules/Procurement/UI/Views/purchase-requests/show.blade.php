<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('P.R.') }} {{ $request->pr_number }}</h2>
            <a href="{{ route('procurement.purchase-requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to P.R. list') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm mb-4">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('P.R. number') }}</dt><dd class="font-mono font-medium">{{ $request->pr_number }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Request date') }}</dt><dd>{{ $request->request_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Requested by') }}</dt><dd>{{ $request->requested_by ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Department') }}</dt><dd>{{ $request->department ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd><span class="px-2 py-0.5 rounded text-xs">{{ $request->status }}</span></dd></div>
            </dl>
            @if(auth()->user()?->can('procurement.manage'))
                @if($request->status === 'draft')
                    <form method="POST" action="{{ route('procurement.purchase-requests.submit', $request->id) }}" class="mt-2 mb-4">
                        @csrf
                        <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Submit P.R.') }}</button>
                    </form>
                @elseif($request->status === 'submitted')
                    <form method="POST" action="{{ route('procurement.purchase-requests.approve', $request->id) }}" class="mt-2 mb-4">
                        @csrf
                        <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-green-600 text-white text-sm hover:bg-green-700">{{ __('Approve P.R.') }}</button>
                    </form>
                @endif
            @endif
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-2">{{ __('Lines') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Description') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Qty') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Est. unit cost') }}</th>
                        <th class="px-4 py-2 text-right font-semibold">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-left font-semibold">{{ __('Account') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($request->lines as $line)
                        <tr>
                            <td class="px-4 py-2">{{ $line->description }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($line->quantity, 4) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($line->estimated_unit_cost, 4) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($line->quantity * $line->estimated_unit_cost, 2) }}</td>
                            <td class="px-4 py-2">{{ $line->account_code ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
