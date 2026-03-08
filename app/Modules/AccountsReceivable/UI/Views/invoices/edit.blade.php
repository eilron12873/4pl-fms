<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit invoice') }} {{ $invoice->invoice_number }}</h2>
            <a href="{{ route('accounts-receivable.invoices.show', $invoice->id) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Invoice') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('accounts-receivable.invoices.update', $invoice->id) }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Client') }} *</label>
                        <select id="client_id" name="client_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ old('client_id', $invoice->client_id) == $c->id ? 'selected' : '' }}>{{ $c->code }} - {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }}</label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', $invoice->currency) }}" maxlength="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Invoice date') }} *</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date?->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Due date') }} *</label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Lines') }} *</label>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody id="lines-tbody">
                            @foreach(old('lines', $invoice->lines->map(fn($l) => ['description' => $l->description, 'amount' => $l->amount])->values()->all()) as $i => $line)
                            <tr class="line-row">
                                <td class="px-4 py-2"><input type="text" name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required></td>
                                <td class="px-4 py-2"><input type="number" name="lines[{{ $i }}][amount]" value="{{ $line['amount'] ?? '' }}" step="0.01" min="0" class="w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required></td>
                            </tr>
                            @endforeach
                            @if($invoice->lines->isEmpty() && !old('lines'))
                            <tr class="line-row">
                                <td class="px-4 py-2"><input type="text" name="lines[0][description]" value="" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required></td>
                                <td class="px-4 py-2"><input type="number" name="lines[0][amount]" value="" step="0.01" min="0" class="w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" required></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Update invoice') }}</button>
                    <a href="{{ route('accounts-receivable.invoices.show', $invoice->id) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
