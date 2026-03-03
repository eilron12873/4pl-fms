<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('General Ledger') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="font-semibold text-lg mb-2">{{ __('Reports') }}</h3>
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Use the links below to access core financial reports powered by the journal engine.') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('general-ledger.trial-balance') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        {{ __('Trial Balance') }}
                    </a>
                    <a href="{{ route('general-ledger.ledger') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-gray-800 text-white text-sm font-medium hover:bg-gray-900">
                        {{ __('General Ledger') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

