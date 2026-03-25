<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Journal Approval') }}: {{ $journal->journal_number }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.journals') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to queue') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $journal->journal_date?->format('Y-m-d') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Period') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $journal->period ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Description') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $journal->description ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $journal->status }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Approval record') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $approval?->status ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Approve / Reject') }}</h3>

            <div class="flex flex-wrap gap-3 items-center">
                <form method="POST" action="{{ route('lfs-administration.approval-workflows.journals.approve', $journal->id) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">{{ __('Approve & Post') }}</button>
                </form>

                <form method="POST" action="{{ route('lfs-administration.approval-workflows.journals.reject', $journal->id) }}">
                    @csrf
                    <div class="w-full md:w-80">
                        <label for="comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Rejection comments (optional)') }}</label>
                        <textarea id="comments" name="comments" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"></textarea>
                    </div>
                    <button type="submit" class="mt-3 inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">{{ __('Reject') }}</button>
                </form>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('Journal Lines') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Account') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Debit') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Credit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($journal->lines as $line)
                            <tr>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $line->account?->code ?? '—' }} — {{ $line->account?->name ?? '' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $line->description ?? '—' }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format((float) $line->debit, 2) }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format((float) $line->credit, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

