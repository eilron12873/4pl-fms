<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Vendor Bill Approval') }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.vendor-bills') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to queue') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Vendor') }}</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        {{ $bill->vendor?->code ?? '' }} - {{ $bill->vendor?->name ?? '' }}
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Bill') }}</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $bill->bill_number }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Bill date') }}</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $bill->bill_date?->format('Y-m-d') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Due date') }}</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $bill->due_date?->format('Y-m-d') }}</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Amount') }}</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        {{ number_format((float) ($bill->total ?? 0), 2) }}
                        <span class="text-sm text-gray-500 dark:text-gray-400">({{ $bill->currency ?? '' }})</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Current bill status') }}</div>
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $bill->status }}</div>

                <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('Approval record (shared engine)') }}</div>
                <div class="font-medium text-gray-900 dark:text-gray-100">
                    @if($approval)
                        {{ $approval->status }}
                    @else
                        {{ __('No approval record created yet (it will be created on approve/reject).') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Approve / Reject') }}</h3>

            <div class="flex flex-wrap gap-3 items-center">
                <form method="POST" action="{{ route('lfs-administration.approval-workflows.vendor-bills.approve', $bill->id) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">
                        {{ __('Approve') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('lfs-administration.approval-workflows.vendor-bills.reject', $bill->id) }}">
                    @csrf
                    <div class="w-full md:w-80">
                        <label for="comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Rejection comments (optional)') }}</label>
                        <textarea id="comments" name="comments" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"></textarea>
                    </div>
                    <button type="submit" class="mt-3 inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                        {{ __('Reject') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

