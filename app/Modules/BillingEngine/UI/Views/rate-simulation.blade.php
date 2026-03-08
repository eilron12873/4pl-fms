<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Rate Simulation') }}</h2>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="GET" action="{{ route('billing-engine.rate-simulation') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Event type') }}</label>
                        <select id="event_type" name="event_type" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="shipment-delivered" {{ ($eventType ?? '') === 'shipment-delivered' ? 'selected' : '' }}>Shipment delivered</option>
                            <option value="storage-accrual" {{ ($eventType ?? '') === 'storage-accrual' ? 'selected' : '' }}>Storage accrual</option>
                            <option value="project-milestone-completed" {{ ($eventType ?? '') === 'project-milestone-completed' ? 'selected' : '' }}>Project milestone completed</option>
                        </select>
                    </div>
                    <div>
                        <label for="event_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Event date') }}</label>
                        <input type="date" id="event_date" name="event_date" value="{{ $payload['event_date'] ?? now()->toDateString() }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    </div>
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Client') }}</label>
                        <select id="client_id" name="client_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">{{ __('Select client') }}</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ ($payload['client_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->code }} - {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="contract_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Contract (optional)') }}</label>
                        <select id="contract_id" name="contract_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">{{ __('Auto from client') }}</option>
                            @foreach($contracts as $c)
                                <option value="{{ $c->id }}" {{ ($payload['contract_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->client->code ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Quantity / Trip') }}</label>
                        <input type="number" id="quantity" name="quantity" step="0.01" min="0" value="{{ $payload['quantity'] ?? 1 }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                    </div>
                    <div>
                        <label for="pallet_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Pallet days') }}</label>
                        <input type="number" id="pallet_days" name="pallet_days" step="0.01" min="0" value="{{ $payload['pallet_days'] ?? '' }}" placeholder="Storage" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                    </div>
                    <div>
                        <label for="cbm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('CBM') }}</label>
                        <input type="number" id="cbm" name="cbm" step="0.01" min="0" value="{{ $payload['cbm'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                    </div>
                    <div>
                        <label for="kg" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('KG') }}</label>
                        <input type="number" id="kg" name="kg" step="0.01" min="0" value="{{ $payload['kg'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                    </div>
                    <div>
                        <label for="trip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Trips') }}</label>
                        <input type="number" id="trip" name="trip" step="1" min="0" value="{{ $payload['trip'] ?? 1 }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-20">
                    </div>
                    <div>
                        <label for="container_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Containers') }}</label>
                        <input type="number" id="container_count" name="container_count" step="1" min="0" value="{{ $payload['container_count'] ?? '' }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm w-24">
                    </div>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Simulate') }}</button>
            </form>
        </div>

        @if(isset($result))
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Result') }}</h3>
                @if($result['contract'])
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ __('Contract') }}: {{ $result['contract']->name }} ({{ $result['contract']->client->code }})</p>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Rate type') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Qty') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Unit price') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($result['lines'] as $line)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $line['description'] }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $line['rate_type']) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($line['quantity'], 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($line['unit_price'], 4) }} {{ $line['currency'] }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($line['amount'], 2) }} {{ $line['currency'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th colspan="4" class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ __('Total') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format($result['total'], 2) }} {{ $result['currency'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <p class="text-gray-500 dark:text-gray-400">{{ __('No contract found for the given client and event type, or no rates defined.') }}</p>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
