<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Core Accounting') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('core-accounting.accounts.index') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-sitemap text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Chart of Accounts') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View and manage accounts') }}</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('core-accounting.journals.index') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Journal Management') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Browse journals and lines') }}</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('core-accounting.posting-sources.index') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-stream text-2xl text-amber-600 dark:text-amber-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Posting Sources') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Financial event ingestion log') }}</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('core-accounting.periods.index') }}"
                   class="block p-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt text-2xl text-purple-600 dark:text-purple-400 mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Period Management') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Open and closed periods') }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
