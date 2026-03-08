<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Billing Clients') }}
            </h2>
            @can('billing-engine.manage')
                <a href="{{ route('billing-engine.clients.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                    {{ __('Add Client') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
            @endif
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Code') }}</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('External ID') }}</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Currency') }}</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($clients as $client)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $client->code }}</td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $client->name }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $client->external_id ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $client->currency }}</td>
                                    <td class="px-4 py-2">
                                        @if($client->is_active)
                                            <span class="text-green-600 dark:text-green-400">{{ __('Active') }}</span>
                                        @else
                                            <span class="text-gray-500">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        @can('billing-engine.manage')
                                            <a href="{{ route('billing-engine.clients.edit', $client->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Edit') }}</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No clients yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($clients->hasPages())
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $clients->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
