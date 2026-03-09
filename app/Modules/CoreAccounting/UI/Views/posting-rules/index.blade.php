<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Posting Rules') }}
            </h2>
            <div class="flex items-center space-x-4">
                <a href="{{ route('core-accounting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    {{ __('Back to Core Accounting') }}
                </a>
                @can('core-accounting.manage')
                    <a href="{{ route('core-accounting.posting-rules.create') }}"
                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600">
                        {{ __('New Rule') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Event type') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Description') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Active') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Lines') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($rules as $rule)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $rule->event_type }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 max-w-md truncate">{{ $rule->description ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        @if($rule->is_active)
                                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('Yes') }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $rule->lines_count }}</td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        @can('core-accounting.manage')
                                            <a href="{{ route('core-accounting.posting-rules.edit', $rule->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ __('Edit') }}
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('No posting rules defined yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($rules->hasPages())
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                        {{ $rules->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

