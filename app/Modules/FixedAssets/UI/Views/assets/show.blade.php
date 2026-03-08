<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $asset->code }} - {{ $asset->name }}</h2>
            <a href="{{ route('fixed-assets.assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to registry') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Type') }}</dt><dd class="font-medium">{{ ucfirst($asset->asset_type) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Purchase date') }}</dt><dd class="font-medium">{{ $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Acquisition cost') }}</dt><dd class="font-medium">{{ number_format($asset->acquisition_cost, 2) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Useful life (years)') }}</dt><dd class="font-medium">{{ $asset->useful_life_years }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Residual value') }}</dt><dd class="font-medium">{{ number_format($asset->residual_value, 2) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Accumulated depreciation') }}</dt><dd class="font-medium">{{ number_format($asset->accumulated_depreciation, 2) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Net book value') }}</dt><dd class="font-medium">{{ number_format($asset->bookValue(), 2) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Last depreciation') }}</dt><dd class="font-medium">{{ $asset->last_depreciation_at ? $asset->last_depreciation_at->format('Y-m-d') : '-' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd class="font-medium"><span class="px-2 py-0.5 rounded text-xs {{ $asset->status === 'active' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-200 dark:bg-gray-600' }}">{{ $asset->status }}</span></dd></div>
                @if($asset->location)<div><dt class="text-gray-500 dark:text-gray-400">{{ __('Location') }}</dt><dd class="font-medium">{{ $asset->location }}</dd></div>@endif
                @if($asset->custodian)<div><dt class="text-gray-500 dark:text-gray-400">{{ __('Custodian') }}</dt><dd class="font-medium">{{ $asset->custodian }}</dd></div>@endif
            </dl>
            @if($asset->notes)<p class="mt-4 text-gray-600 dark:text-gray-400">{{ $asset->notes }}</p>@endif
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <h3 class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">{{ __('Maintenance records') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($asset->maintenanceRecords as $m)
                            <tr>
                                <td class="px-4 py-2">{{ $m->maintenance_date ? $m->maintenance_date->format('Y-m-d') : '' }}</td>
                                <td class="px-4 py-2">{{ $m->description ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($m->amount, 2) }}</td>
                                <td class="px-4 py-2">{{ $m->reference ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">{{ __('No maintenance records.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
