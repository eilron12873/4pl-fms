<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $contract->name }}</h2>
            <div class="flex gap-2">
                @can('billing-engine.manage')
                    <a href="{{ route('billing-engine.contracts.edit', $contract->id) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Edit') }}</a>
                @endcan
                <a href="{{ route('billing-engine.contracts.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))
            <div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Client') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ $contract->client->code }} — {{ $contract->client->name }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Service type') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ $contract->serviceType->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Effective') }}</dt><dd class="font-medium text-gray-900 dark:text-gray-100">{{ $contract->effective_from?->format('Y-m-d') }} – {{ $contract->effective_to?->format('Y-m-d') ?? 'Ongoing' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd><span class="px-2 py-0.5 rounded text-xs {{ $contract->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $contract->status }}</span></dd></div>
                @if($contract->contract_number)<div><dt class="text-gray-500 dark:text-gray-400">{{ __('Contract number') }}</dt><dd class="font-mono text-gray-900 dark:text-gray-100">{{ $contract->contract_number }}</dd></div>@endif
            </dl>
            @if($contract->sla_terms)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <dt class="text-gray-500 dark:text-gray-400 text-sm">{{ __('SLA terms') }}</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $contract->sla_terms }}</dd>
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Rate definitions') }}</h3>
            @can('billing-engine.manage')
                <form method="POST" action="{{ route('billing-engine.contracts.rates.store', $contract->id) }}" class="mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 space-y-3">
                    @csrf
                    <div class="flex flex-wrap gap-3 items-end">
                        <div>
                            <label for="rate_type" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Rate type') }}</label>
                            <select id="rate_type" name="rate_type" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                                <option value="per_pallet_day">Per pallet/day</option>
                                <option value="per_cbm">Per CBM</option>
                                <option value="per_kg">Per KG</option>
                                <option value="per_trip">Per trip</option>
                                <option value="per_route">Per route</option>
                                <option value="per_container">Per container</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div>
                            <label for="unit_price" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Unit price') }}</label>
                            <input type="number" id="unit_price" name="unit_price" step="0.0001" min="0" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                        </div>
                        <div>
                            <label for="currency" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Currency') }}</label>
                            <input type="text" id="currency" name="currency" value="{{ $contract->client->currency ?? 'USD' }}" maxlength="3" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-20">
                        </div>
                        <div>
                            <label for="min_quantity" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Min qty') }}</label>
                            <input type="number" id="min_quantity" name="min_quantity" step="0.0001" min="0" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                        </div>
                        <div>
                            <label for="max_quantity" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Max qty') }}</label>
                            <input type="number" id="max_quantity" name="max_quantity" step="0.0001" min="0" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                        </div>
                        <div>
                            <label for="description" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Description') }}</label>
                            <input type="text" id="description" name="description" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-40">
                        </div>
                        <button type="submit" class="inline-flex px-3 py-1.5 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Add rate') }}</button>
                    </div>
                </form>
            @endcan
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Rate type') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Unit price') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Tier (min–max)') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                            @can('billing-engine.manage')<th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300"></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($contract->rateDefinitions as $rate)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ str_replace('_', ' ', $rate->rate_type) }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ number_format($rate->unit_price, 4) }} {{ $rate->currency }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $rate->min_quantity !== null ? number_format($rate->min_quantity, 2) : '—' }} – {{ $rate->max_quantity !== null ? number_format($rate->max_quantity, 2) : '—' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $rate->description ?? '—' }}</td>
                                @can('billing-engine.manage')
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('billing-engine.contracts.rates.destroy', [$contract->id, $rate->id]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this rate?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-xs">{{ __('Remove') }}</button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="{{ auth()->user()?->can('billing-engine.manage') ? 5 : 4 }}" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">{{ __('No rate definitions yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
