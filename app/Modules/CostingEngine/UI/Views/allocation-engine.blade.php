<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Allocation Engine') }}</h2>
            <a href="{{ route('costing-engine.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Costing') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if($message)<div class="mb-4 p-3 rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm">{{ $message }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Create allocation rule') }}</h3>
                <form method="POST" action="{{ route('costing-engine.allocation-rules.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <input type="text" name="name" placeholder="{{ __('Rule name') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
                    <select name="rule_type" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="revenue_proportion">{{ __('Revenue proportion') }}</option>
                        <option value="volume">{{ __('Volume-based') }}</option>
                        <option value="fixed">{{ __('Fixed amount') }}</option>
                        <option value="percentage">{{ __('Percentage') }}</option>
                    </select>
                    <select name="target_dimension" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="client_id">{{ __('Client') }}</option>
                        <option value="shipment_id">{{ __('Shipment') }}</option>
                        <option value="route_id">{{ __('Route') }}</option>
                        <option value="warehouse_id">{{ __('Warehouse') }}</option>
                        <option value="project_id">{{ __('Project') }}</option>
                    </select>
                    <input type="number" step="0.01" min="0" name="pool_amount" placeholder="{{ __('Pool amount') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <input type="number" step="0.0001" min="0" max="100" name="percentage" placeholder="{{ __('Percentage') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <input type="number" step="0.01" min="0" name="fixed_amount" placeholder="{{ __('Fixed amount') }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <input type="date" name="effective_from" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <input type="date" name="effective_to" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">{{ __('Create rule') }}</button>
                </form>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Run allocation batch') }}</h3>
                <form method="GET" action="{{ route('costing-engine.allocation-engine') }}" class="flex gap-3 items-end">
                    <input type="date" name="run_date" value="{{ now()->toDateString() }}" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-amber-600 text-white text-sm hover:bg-amber-700">{{ __('Run now') }}</button>
                </form>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Rules') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ __('Name') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Type') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Target') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Fixed') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Percentage') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Active') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($rules as $rule)
                            <tr>
                                <td class="px-3 py-2">{{ $rule->name }}</td>
                                <td class="px-3 py-2">{{ $rule->rule_type }}</td>
                                <td class="px-3 py-2">{{ $rule->target_dimension }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float) $rule->fixed_amount, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float) $rule->percentage, 4) }}</td>
                                <td class="px-3 py-2">{{ $rule->is_active ? __('Yes') : __('No') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No allocation rules yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($rules->hasPages())<div class="mt-3">{{ $rules->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
