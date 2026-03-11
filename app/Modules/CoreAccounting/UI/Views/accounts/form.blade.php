<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $mode === 'edit' ? __('Edit Account') : __('New Account') }}
            </h2>
            <a href="{{ route('core-accounting.accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Chart of Accounts') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <form method="POST"
                          action="{{ $mode === 'edit' ? route('core-accounting.accounts.update', $account->id) : route('core-accounting.accounts.store') }}">
                        @csrf
                        @if($mode === 'edit')
                            @method('PUT')
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Code') }}
                                </label>
                                <input id="code" name="code" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                       value="{{ old('code', $account->code) }}"
                                       {{ $mode === 'edit' ? 'readonly' : '' }}
                                       required>
                                @if($mode === 'edit')
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Account code cannot be changed once created to preserve mappings.') }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Name') }}
                                </label>
                                <input id="name" name="name" type="text"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                       value="{{ old('name', $account->name) }}"
                                       required>
                            </div>

                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Type') }}
                                </label>
                                <select id="type" name="type"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                        required>
                                    @php
                                        $type = old('type', $account->type ?? 'asset');
                                    @endphp
                                    <option value="asset" @selected($type === 'asset')>{{ __('Asset') }}</option>
                                    <option value="liability" @selected($type === 'liability')>{{ __('Liability') }}</option>
                                    <option value="equity" @selected($type === 'equity')>{{ __('Equity') }}</option>
                                    <option value="revenue" @selected($type === 'revenue')>{{ __('Revenue') }}</option>
                                    <option value="expense" @selected($type === 'expense')>{{ __('Expense') }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Parent account (optional)') }}
                                </label>
                                <select id="parent_id" name="parent_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                    <option value="">{{ __('(none)') }}</option>
                                    @foreach($parentOptions as $parent)
                                        <option value="{{ $parent->id }}"
                                            @selected(old('parent_id', $account->parent_id) == $parent->id)>
                                            {{ $parent->code }} — {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Level') }}
                                </label>
                                <input id="level" name="level" type="number" min="1" max="9"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                       value="{{ old('level', $account->level ?? 2) }}">
                            </div>

                            <div class="flex items-center mt-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_posting" value="1"
                                           class="rounded border-gray-300 dark:border-gray-700 text-teal-600 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                           {{ old('is_posting', $account->is_posting ?? true) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Posting account') }}</span>
                                </label>
                            </div>

                            <div class="flex items-center mt-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_active" value="1"
                                           class="rounded border-gray-300 dark:border-gray-700 text-teal-600 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                           {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                {{ $mode === 'edit' ? __('Save changes') : __('Create account') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

