<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Vendors') }}</h2>
            @can('accounts-payable.manage')
                <a href="{{ route('accounts-payable.vendors.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Add vendor') }}</a>
            @endcan
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
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Category') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Currency') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Terms (days)') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Preferred payment') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($vendors as $v)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $v->code }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $v->name }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $v->category ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $v->currency }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ $v->payment_terms_days }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                    @if($v->preferred_payment_method === 'ach')
                                        {{ __('ACH / Bank transfer') }}
                                    @elseif($v->preferred_payment_method === 'check')
                                        {{ __('Check') }}
                                    @elseif($v->preferred_payment_method === 'other')
                                        {{ __('Other') }}
                                    @else
                                        {{ __('Not specified') }}
                                    @endif
                                </td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs {{ $v->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $v->is_active ? __('Active') : __('Inactive') }}</span></td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('accounts-payable.vendors.show', $v) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View') }}</a>
                                    @can('accounts-payable.manage')
                                        <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                                        <a href="{{ route('accounts-payable.vendors.edit', $v) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Edit') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No vendors yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vendors->hasPages())<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">{{ $vendors->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
