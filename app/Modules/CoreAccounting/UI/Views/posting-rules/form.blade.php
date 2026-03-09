<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $mode === 'edit' ? __('Edit Posting Rule') : __('New Posting Rule') }}
            </h2>
            <a href="{{ route('core-accounting.posting-rules.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Posting Rules') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <form method="POST"
                          action="{{ $mode === 'edit' ? route('core-accounting.posting-rules.update', $rule->id) : route('core-accounting.posting-rules.store') }}">
                        @csrf
                        @if($mode === 'edit')
                            @method('PUT')
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-1">
                                <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Event type') }}
                                </label>
                                <input id="event_type" name="event_type" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                       value="{{ old('event_type', $rule->event_type) }}"
                                       placeholder="e.g. shipment-delivered" required>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Must match the financial event type (e.g. shipment-delivered, storage-accrual).') }}
                                </p>
                            </div>

                            <div class="md:col-span-1">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Description') }}
                                </label>
                                <input id="description" name="description" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                       value="{{ old('description', $rule->description) }}">
                            </div>

                            <div class="md:col-span-1 flex items-center mt-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_active" value="1"
                                           class="rounded border-gray-300 dark:border-gray-700 text-teal-600 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                           {{ old('is_active', $rule->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                {{ __('Posting lines') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                {{ __('Define debit and credit lines. Amount comes from a payload field (e.g. amount). Dimensions can be mapped from payload fields using the checkboxes.') }}
                            </p>

                            @php
                                $oldLines = old('lines', $rule->lines?->toArray() ?? []);
                                if (empty($oldLines)) {
                                    $oldLines = [
                                        ['entry_type' => 'debit'],
                                        ['entry_type' => 'credit'],
                                    ];
                                }
                            @endphp

                            <div class="space-y-4">
                                @foreach($oldLines as $index => $line)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('Account') }}
                                                </label>
                                                <select name="lines[{{ $index }}][account_id]"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                                    <option value="">{{ __('Select account') }}</option>
                                                    @foreach($accounts as $account)
                                                        <option value="{{ $account->id }}"
                                                            @selected(($line['account_id'] ?? null) == $account->id)>
                                                            {{ $account->code }} — {{ $account->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('Entry type') }}
                                                </label>
                                                <select name="lines[{{ $index }}][entry_type]"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                                    <option value="debit" @selected(($line['entry_type'] ?? null) === 'debit')>{{ __('Debit') }}</option>
                                                    <option value="credit" @selected(($line['entry_type'] ?? null) === 'credit')>{{ __('Credit') }}</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('Amount source field') }}
                                                </label>
                                                <input type="text"
                                                       name="lines[{{ $index }}][amount_source]"
                                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                                       value="{{ $line['amount_source'] ?? old("lines.$index.amount_source", 'amount') }}"
                                                       placeholder="amount">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('Dimensions') }}
                                                </label>
                                                @php
                                                    $dim = $line['dimension_source'] ?? [];
                                                @endphp
                                                <div class="mt-1 grid grid-cols-2 gap-1 text-xs text-gray-700 dark:text-gray-300">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_client_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['client_id']))>
                                                        <span class="ml-1">client_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_shipment_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['shipment_id']))>
                                                        <span class="ml-1">shipment_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_route_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['route_id']))>
                                                        <span class="ml-1">route_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_warehouse_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['warehouse_id']))>
                                                        <span class="ml-1">warehouse_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_vehicle_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['vehicle_id']))>
                                                        <span class="ml-1">vehicle_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_project_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['project_id']))>
                                                        <span class="ml-1">project_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_service_line_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['service_line_id']))>
                                                        <span class="ml-1">service_line_id</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="lines[{{ $index }}][map_cost_center_id]" value="1"
                                                               class="rounded border-gray-300 dark:border-gray-700 text-teal-600 focus:ring-teal-500"
                                                               @checked(isset($dim['cost_center_id']))>
                                                        <span class="ml-1">cost_center_id</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="md:col-span-1">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('Resolver type (optional)') }}
                                                </label>
                                                <input type="text"
                                                       name="lines[{{ $index }}][resolver_type]"
                                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                                       value="{{ $line['resolver_type'] ?? old("lines.$index.resolver_type") }}"
                                                       placeholder="e.g. revenue_by_service_line">
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ __('Leave blank to use the account above. When set, Account Resolvers can override this line account dynamically.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('For most events, you will use one debit and one credit line. Additional lines can be added later by a developer if needed.') }}
                            </p>
                        </div>

                        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                {{ __('Conditions (optional)') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                {{ __('Use conditions to apply this rule only when certain payload fields match (e.g. shipment_type = subcontracted). If no condition matches, the engine falls back to the first active rule for the event.') }}
                            </p>

                            @php
                                $oldConditions = old('conditions', $rule->conditions?->toArray() ?? []);
                                if (empty($oldConditions)) {
                                    $oldConditions = [
                                        [],
                                    ];
                                }
                            @endphp

                            <div class="space-y-3">
                                @foreach($oldConditions as $cIndex => $condition)
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Field name') }}
                                            </label>
                                            <input type="text"
                                                   name="conditions[{{ $cIndex }}][field_name]"
                                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                                   value="{{ $condition['field_name'] ?? old("conditions.$cIndex.field_name") }}"
                                                   placeholder="e.g. shipment_type">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Operator') }}
                                            </label>
                                            <select name="conditions[{{ $cIndex }}][operator]"
                                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                                @php
                                                    $op = $condition['operator'] ?? old("conditions.$cIndex.operator");
                                                @endphp
                                                <option value="">{{ __('(none)') }}</option>
                                                <option value="=" @selected($op === '=')>=</option>
                                                <option value="!=" @selected($op === '!=')>!=</option>
                                                <option value=">" @selected($op === '>')>&gt;</option>
                                                <option value="<" @selected($op === '<')>&lt;</option>
                                                <option value="IN" @selected($op === 'IN')>IN</option>
                                                <option value="NOT IN" @selected($op === 'NOT IN')>NOT IN</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Comparison value') }}
                                            </label>
                                            <input type="text"
                                                   name="conditions[{{ $cIndex }}][comparison_value]"
                                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                                   value="{{ $condition['comparison_value'] ?? old("conditions.$cIndex.comparison_value") }}"
                                                   placeholder="e.g. subcontracted or A,B,C for IN">
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Leave all condition fields empty to make this rule unconditional.') }}
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                {{ $mode === 'edit' ? __('Save changes') : __('Create rule') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

