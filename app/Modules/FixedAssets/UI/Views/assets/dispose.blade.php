<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Dispose asset') }}: {{ $asset->code }}</h2>
            <a href="{{ route('fixed-assets.assets.show', $asset->id) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-4 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('Disposal will post a GL journal (gain/loss) via Core Accounting and mark the asset as disposed.') }}</p>
                <div class="text-sm text-gray-700 dark:text-gray-200">
                    <div><span class="font-medium">{{ __('Net book value') }}:</span> <span class="font-mono">{{ number_format($asset->bookValue(), 2) }}</span></div>
                    <div><span class="font-medium">{{ __('Acquisition cost') }}:</span> <span class="font-mono">{{ number_format((float) $asset->acquisition_cost, 2) }}</span></div>
                    <div><span class="font-medium">{{ __('Accumulated depreciation') }}:</span> <span class="font-mono">{{ number_format((float) $asset->accumulated_depreciation, 2) }}</span></div>
                </div>
            </div>

            <form method="POST" action="{{ route('fixed-assets.assets.dispose.store', $asset->id) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="proceeds" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Proceeds') }} *</label>
                        <input
                            type="number"
                            id="proceeds"
                            name="proceeds"
                            step="0.01"
                            min="0"
                            value="{{ old('proceeds') }}"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                        >
                        @error('proceeds')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="disposed_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Disposed at') }} *</label>
                        <input type="date" id="disposed_at" name="disposed_at" value="{{ old('disposed_at', now()->toDateString()) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('disposed_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Reference') }}</label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference') }}"
                               maxlength="255"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">{{ __('Dispose and post GL') }}</button>
                    <a href="{{ route('fixed-assets.assets.show', $asset->id) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

