<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Audit entry') }} #{{ $activity->id }}</h2>
            <a href="{{ route('lfs-administration.audit-logs', request()->query()) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to list') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm space-y-3">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $activity->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Category') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $activity->log_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Event') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $activity->event ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('User') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $activity->causer?->name ?? $activity->causer?->email ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Description') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $activity->description }}</dd>
                </div>
                @if($activity->subject_type)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Subject') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100 break-all">
                            {{ $activity->subject_type }} #{{ $activity->subject_id }}
                            @if(!empty($subjectUrl))
                                <a href="{{ $subjectUrl }}" class="ml-2 text-blue-600 dark:text-blue-400 hover:underline">{{ __('Open record') }}</a>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Properties') }}</h3>
            @php
                $props = $activity->properties ?? [];
            @endphp
            @if(empty($props))
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No additional properties.') }}</p>
            @else
                <pre class="text-xs bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-gray-800 dark:text-gray-200">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    </div>
</x-app-layout>
