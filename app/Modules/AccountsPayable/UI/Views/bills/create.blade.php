<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Create bill (AP Entry)') }}</h2>
            <a href="{{ route('accounts-payable.bills.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Bills') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
                <ul class="list-disc list-inside">{{ implode('', $errors->all('<li>:message</li>')) }}</ul>
            </div>
        @endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('accounts-payable.bills.store') }}" id="bill-form">
                @csrf
                @if(isset($purchaseOrder) && $purchaseOrder)
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Vendor') }} *</label>
                        <select id="vendor_id" name="vendor_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">{{ __('Select vendor') }}</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id', optional($purchaseOrder ?? null)->vendor_id) == $v->id ? 'selected' : '' }}>{{ $v->code }} - {{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }}</label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', optional($purchaseOrder ?? null)->currency ?? 'USD') }}" maxlength="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="bill_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bill date') }} *</label>
                        <input type="date" id="bill_date" name="bill_date" value="{{ old('bill_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Due date') }} *</label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('notes', isset($purchaseOrder) && $purchaseOrder ? 'From P.O. ' . $purchaseOrder->po_number : '') }}</textarea>
                </div>
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Lines') }} *</label>
                        <button type="button" id="add-line" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Add line') }}</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm" id="lines-table">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300 w-2/3">{{ __('Description') }}</th>
                                    <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 w-1/3">{{ __('Amount') }}</th>
                                    <th class="px-4 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="lines-tbody">
                                @foreach(old('lines', $presetLines ?? [['description' => '', 'amount' => '']]) as $i => $line)
                                <tr class="line-row">
                                    <td class="px-4 py-2"><input type="text" name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" placeholder="{{ __('Description') }}"></td>
                                    <td class="px-4 py-2"><input type="number" name="lines[{{ $i }}][amount]" value="{{ $line['amount'] ?? '' }}" step="0.01" min="0" class="w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" placeholder="0.00"></td>
                                    <td class="px-4 py-2"><button type="button" class="remove-line text-red-600 hover:text-red-800 text-sm">{{ __('Remove') }}</button></td>
                                </tr>
                                @endforeach
                                @if(count(old('lines', $presetLines ?? [])) === 0)
                                <tr class="line-row">
                                    <td class="px-4 py-2"><input type="text" name="lines[0][description]" value="" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" placeholder="{{ __('Description') }}"></td>
                                    <td class="px-4 py-2"><input type="number" name="lines[0][amount]" value="" step="0.01" min="0" class="w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" placeholder="0.00"></td>
                                    <td class="px-4 py-2"><button type="button" class="remove-line text-red-600 hover:text-red-800 text-sm">{{ __('Remove') }}</button></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Create bill') }}</button>
                    <a href="{{ route('accounts-payable.bills.index') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('lines-tbody');
        var addBtn = document.getElementById('add-line');
        addBtn.addEventListener('click', function() {
            var idx = tbody.querySelectorAll('.line-row').length;
            var tr = document.createElement('tr');
            tr.className = 'line-row';
            tr.innerHTML = '<td class="px-4 py-2"><input type="text" name="lines[' + idx + '][description]" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" placeholder="{{ __("Description") }}"></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][amount]" step="0.01" min="0" class="w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" placeholder="0.00"></td>' +
                '<td class="px-4 py-2"><button type="button" class="remove-line text-red-600 hover:text-red-800 text-sm">{{ __("Remove") }}</button></td>';
            tbody.appendChild(tr);
            tr.querySelector('.remove-line').addEventListener('click', function() { removeRow(tr); });
        });
        tbody.querySelectorAll('.remove-line').forEach(function(btn) {
            btn.addEventListener('click', function() { removeRow(btn.closest('tr')); });
        });
        function removeRow(tr) {
            if (tbody.querySelectorAll('.line-row').length <= 1) return;
            tr.remove();
        }
    });
    </script>
</x-app-layout>
