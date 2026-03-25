<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __($title) }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Approval Workflows') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __($note) }}</p>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Go to Administration') }}</a>
        </div>
    </div>
</x-app-layout>

