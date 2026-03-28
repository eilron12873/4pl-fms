<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Financial Controls') }}</h2>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 p-3 rounded-md bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        <div class="mb-4 p-4 rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-100 text-sm">
            {{ __('Changes here affect journal posting behavior across Core Accounting and related flows.') }}
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('lfs-administration.settings.financial-controls.update') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Max backdating (days)') }}</label>
                    <input type="number" name="max_backdating_days" min="0" max="3650" value="{{ old('max_backdating_days', $controls->max_backdating_days) }}" class="w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Leave empty for no limit. Journal date must not be earlier than today minus this many days.') }}</p>
                    @error('max_backdating_days')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="allow_manual_journals" value="0">
                    <input type="checkbox" name="allow_manual_journals" value="1" id="allow_manual_journals" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700" @checked(old('allow_manual_journals', $controls->allow_manual_journals))>
                    <label for="allow_manual_journals" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Allow manual journals and template-based postings without integration source') }}</label>
                </div>
                <div class="pt-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Save') }}</button>
                </div>
            </form>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-700 dark:text-gray-300 space-y-2">
                <p><span class="font-medium">{{ __('Max backdating (days)') }}:</span> {{ $controls->max_backdating_days ?? __('Unlimited') }}</p>
                <p><span class="font-medium">{{ __('Manual journals') }}:</span> {{ $controls->allow_manual_journals ? __('Enabled') : __('Disabled') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mt-4">{{ __('You do not have permission to edit financial controls.') }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
