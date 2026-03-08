<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit Contract') }}</h2>
    </x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('billing-engine.contracts.update', $contract->id) }}">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Client') }} *</label>
                        <select id="client_id" name="client_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ old('client_id', $contract->client_id) == $c->id ? 'selected' : '' }}>{{ $c->code }} - {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="service_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Service Type') }} *</label>
                        <select id="service_type_id" name="service_type_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @foreach($serviceTypes as $st)
                                <option value="{{ $st->id }}" {{ old('service_type_id', $contract->service_type_id) == $st->id ? 'selected' : '' }}>{{ $st->code }} - {{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }} *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $contract->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contract number') }}</label>
                        <input type="text" id="contract_number" name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="effective_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Effective from') }} *</label>
                            <input type="date" id="effective_from" name="effective_from" value="{{ old('effective_from', $contract->effective_from?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                        <div>
                            <label for="effective_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Effective to') }}</label>
                            <input type="date" id="effective_to" name="effective_to" value="{{ old('effective_to', $contract->effective_to?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }} *</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="draft" {{ old('status', $contract->status) === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="active" {{ old('status', $contract->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="expired" {{ old('status', $contract->status) === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="sla_terms" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SLA terms') }}</label>
                        <textarea id="sla_terms" name="sla_terms" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('sla_terms', $contract->sla_terms) }}</textarea>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Update') }}</button>
                    <a href="{{ route('billing-engine.contracts.show', $contract->id) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
