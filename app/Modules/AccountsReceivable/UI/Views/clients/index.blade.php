<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Clients') }}</h2>
            @canany(['accounts-receivable.manage', 'accounts-receivable.clients.manage'])
                <a href="{{ route('accounts-receivable.clients.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Add client') }}</a>
            @endcanany
        </div>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>@endif
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
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 min-w-[14rem]">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($clients as $cl)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $cl->code }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $cl->display_name }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $cl->external_id ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $cl->currency }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $cl->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $cl->is_active ? __('Active') : __('Inactive') }}</span>
                                </td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('accounts-receivable.clients.show', $cl) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a>
                                    @canany(['accounts-receivable.manage', 'accounts-receivable.clients.manage'])
                                        <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                                        <a href="{{ route('accounts-receivable.clients.edit', $cl) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Edit') }}</a>
                                        <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                                        <form method="POST" action="{{ route('accounts-receivable.clients.toggle-active', $cl) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline bg-transparent border-0 p-0 cursor-pointer text-sm font-inherit">
                                                {{ $cl->is_active ? __('Deactivate') : __('Activate') }}
                                            </button>
                                        </form>
                                    @endcanany
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No clients yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($clients->hasPages())<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $clients->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
