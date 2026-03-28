<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Tax Configuration') }}</h2>
            <div class="flex gap-3">
                @if($canManage)
                    <a href="{{ route('lfs-administration.settings.tax.codes.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Add tax code') }}</a>
                @endif
                <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="p-3 rounded-md bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        @forelse($taxCodes as $tc)
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <div class="flex flex-wrap justify-between gap-2 mb-3">
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $tc->code }}</span>
                        <span class="text-gray-600 dark:text-gray-400">— {{ $tc->name }}</span>
                        <span class="text-xs ml-2 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ strtoupper($tc->type) }}</span>
                        @if(!$tc->is_active)<span class="text-xs text-red-600">{{ __('Inactive') }}</span>@endif
                    </div>
                    @if($canManage)
                        <a href="{{ route('lfs-administration.settings.tax.codes.edit', $tc) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Edit code') }}</a>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    {{ __('Input GL') }}: {{ $tc->inputAccount?->code ?? '—' }} · {{ __('Output GL') }}: {{ $tc->outputAccount?->code ?? '—' }}
                    · {{ $tc->is_inclusive ? __('Tax inclusive') : __('Tax exclusive') }}
                </p>
                <table class="min-w-full text-sm mb-4">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-1 pr-4">{{ __('Rate %') }}</th>
                        <th class="py-1 pr-4">{{ __('Effective from') }}</th>
                        <th class="py-1">{{ __('Effective to') }}</th>
                    </tr></thead>
                    <tbody>
                        @forelse($tc->rates as $r)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-1 pr-4">{{ $r->rate }}</td>
                                <td class="py-1 pr-4">{{ $r->effective_from?->toDateString() }}</td>
                                <td class="py-1">{{ $r->effective_to?->toDateString() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-500">{{ __('No rates yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($canManage)
                    <form method="POST" action="{{ route('lfs-administration.settings.tax.rates.store', $tc) }}" class="flex flex-wrap gap-3 items-end border-t border-gray-200 dark:border-gray-700 pt-4">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('New rate %') }}</label>
                            <input type="number" name="rate" step="0.0001" min="0" max="100" required class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('Effective from') }}</label>
                            <input type="date" name="effective_from" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('Effective to') }}</label>
                            <input type="date" name="effective_to" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        </div>
                        <button type="submit" class="px-3 py-2 rounded-md bg-gray-700 text-white text-sm hover:bg-gray-800">{{ __('Add rate') }}</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                {{ __('No tax codes defined yet.') }}
                @if($canManage)
                    <p class="mt-2"><a href="{{ route('lfs-administration.settings.tax.codes.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Add tax code') }}</a></p>
                @endif
            </div>
        @endforelse
    </div>
</x-app-layout>
