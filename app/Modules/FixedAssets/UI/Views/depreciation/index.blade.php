<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Depreciation') }}</h2>
            <a href="{{ route('fixed-assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('fixed-assets.depreciation.schedule') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Depreciation schedule') }}</a>
            <a href="{{ route('fixed-assets.depreciation.history') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Depreciation history') }}</a>
        </div>
        @if(session('success'))
            <div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('Run straight-line depreciation for all active assets for a given month. Depreciation is posted to the GL (Depreciation expense / Accumulated depreciation). Each period can only be run once (idempotent).') }}</p>
            @can('fixed-assets.manage')
                <form method="POST" action="{{ route('fixed-assets.depreciation.run') }}" class="flex flex-wrap gap-4 items-end">
                    @csrf
                    <div>
                        <label for="period_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Period end date (e.g. last day of month)') }} *</label>
                        <input type="date" id="period_end_date" name="period_end_date" value="{{ old('period_end_date', now()->endOfMonth()->toDateString()) }}" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('period_end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">{{ __('Run depreciation') }}</button>
                </form>
            @else
                <p class="text-gray-500 dark:text-gray-400">{{ __('You do not have permission to run depreciation.') }}</p>
            @endcan
        </div>
    </div>
</x-app-layout>
