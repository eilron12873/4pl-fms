<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Allocation Approval') }}: {{ $run->id }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.allocations') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to queue') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Run date') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $run->run_date?->format('Y-m-d') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $run->status }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Approval record') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $approval?->status ?? '—' }}</dd>
                </div>
                @if(! empty($run->comments))
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Comments') }}</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $run->comments }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Approve / Reject') }}</h3>

            <div class="flex flex-wrap gap-3 items-center">
                <form method="POST" action="{{ route('lfs-administration.approval-workflows.allocations.approve', $run->id) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">{{ __('Approve & Apply') }}</button>
                </form>

                <form method="POST" action="{{ route('lfs-administration.approval-workflows.allocations.reject', $run->id) }}">
                    @csrf
                    <div class="w-full md:w-80">
                        <label for="comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Rejection comments (optional)') }}</label>
                        <textarea id="comments" name="comments" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"></textarea>
                    </div>
                    <button type="submit" class="mt-3 inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">{{ __('Reject') }}</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

