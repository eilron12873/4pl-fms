<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit vendor') }}: {{ $vendor->code }}</h2>
            <a href="{{ route('accounts-payable.vendors.show', $vendor) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('View') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('accounts-payable.vendors.update', $vendor) }}">
                @csrf
                @method('PUT')
                @include('accounts-payable::vendors._form', ['vendor' => $vendor])
                <div class="mt-6 flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Save changes') }}</button>
                    <a href="{{ route('accounts-payable.vendors.show', $vendor) }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
