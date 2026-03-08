<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Period Management') }}
            </h2>
            <a href="{{ route('core-accounting.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Back to Core Accounting') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-md bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">{{ session('error') }}</div>
            @endif
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Code') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Start date') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('End date') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Closed at') }}</th>
                                @can('core-accounting.manage')
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($periods as $period)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $period->code }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $period->start_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $period->end_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-0.5 rounded text-xs {{ $period->status === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $period->status }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $period->closed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    @can('core-accounting.manage')
                                        <td class="px-4 py-2 text-sm text-right">
                                            @if($period->isOpen())
                                                <form method="POST" action="{{ route('core-accounting.periods.close', $period->id) }}" class="inline" onsubmit="return confirm('{{ __('Close this period? No further posting will be allowed for this period.') }}');">
                                                    @csrf
                                                    <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">{{ __('Close') }}</button>
                                                </form>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()?->can('core-accounting.manage') ? 6 : 5 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No periods defined. Run PeriodsSeeder.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($periods->hasPages())
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                        {{ $periods->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
