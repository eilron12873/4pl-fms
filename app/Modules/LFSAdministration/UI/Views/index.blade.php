<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Governance, Security & Observability') }}</h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <a href="{{ route('lfs-administration.audit-logs') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600"><i class="fas fa-clipboard-check"></i></span>
                    {{ __('Audit Logs') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('User activity and financial posting log') }}</p>
            </a>
            <a href="{{ route('lfs-administration.roles') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition">
                <span class="flex items-center gap-3 text-gray-900 dark:text-gray-100 font-medium">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600"><i class="fas fa-users-cog"></i></span>
                    {{ __('Role & Permission Management') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Roles and permissions') }}</p>
            </a>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Recent financial events') }}</h3>
            <ul class="space-y-2 text-sm">
                @forelse($recentFinancial ?? [] as $a)
                    <li class="text-gray-700 dark:text-gray-300">{{ $a->created_at->format('Y-m-d H:i') }} {{ $a->description }} @if($a->causer)({{ $a->causer->name ?? $a->causer->email }})@endif</li>
                @empty
                    <li class="text-gray-500">{{ __('No financial events yet.') }}</li>
                @endforelse
            </ul>
            <p class="mt-4"><a href="{{ route('lfs-administration.audit-logs') }}?log_name=financial" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all audit logs') }}</a></p>
        </div>
        <div class="mt-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <p class="text-sm text-gray-500">{{ __('Environment') }}: {{ $appEnv ?? '—' }} · Laravel {{ $laravelVersion ?? '—' }}</p>
        </div>
    </div>
</x-app-layout>

