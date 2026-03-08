<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Maintenance Cost Tracking') }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('fixed-assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
                @can('fixed-assets.manage')
                    <a href="{{ route('fixed-assets.maintenance.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Record maintenance') }}</a>
                @endcan
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))
            <div class="p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('fixed-assets.maintenance.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="fixed_asset_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Asset') }}</label>
                    <select id="fixed_asset_id" name="fixed_asset_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}" @selected(request('fixed_asset_id') == $a->id)>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-gray-600 text-white text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($records as $r)
                            <tr>
                                <td class="px-4 py-2">{{ $r->maintenance_date ? $r->maintenance_date->format('Y-m-d') : '' }}</td>
                                <td class="px-4 py-2">{{ $r->fixedAsset ? $r->fixedAsset->code : '-' }}</td>
                                <td class="px-4 py-2">{{ $r->description ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($r->amount, 2) }}</td>
                                <td class="px-4 py-2">{{ $r->reference ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No maintenance records.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
