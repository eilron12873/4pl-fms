<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Account') }}: {{ $account->code }} — {{ $account->name }}
            </h2>
            <a href="{{ route('core-accounting.accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Chart of Accounts') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $account->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 capitalize">{{ $account->type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Posting') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->is_posting ? __('Yes') : __('No') }}</dd>
                    </div>
                    @if($account->parent)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Parent') }}</dt>
                            <dd class="mt-1">
                                <a href="{{ route('core-accounting.accounts.show', $account->parent_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $account->parent->code }} — {{ $account->parent->name }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
            @if($account->children->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <h3 class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">{{ __('Child accounts') }}</h3>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($account->children as $child)
                            <li class="px-4 py-2 flex justify-between">
                                <a href="{{ route('core-accounting.accounts.show', $child->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $child->code }} — {{ $child->name }}
                                </a>
                                <span class="text-gray-500 dark:text-gray-400 text-sm capitalize">{{ $child->type }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
