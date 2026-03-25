<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Credit Note Approval Queue') }}</h2>
            <a href="{{ route('lfs-administration.approval-workflows.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Approval Workflows') }}</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('These credit note requests are pending approval. Journals are posted only after approval.') }}</p>
            </div>

            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Approval ID') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reference') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Adjustment') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Requested at') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($approvals as $approval)
                            @php
                                $adjustment = $approval->approvable;
                                $reference = '';
                                if ($adjustment instanceof \\App\\Modules\\AccountsReceivable\\Infrastructure\\Models\\ArInvoiceAdjustment) {
                                    $reference = ($adjustment->invoice?->invoice_number ?? '') . ' (AR)';
                                } elseif ($adjustment instanceof \\App\\Modules\\AccountsPayable\\Infrastructure\\Models\\ApBillAdjustment) {
                                    $reference = ($adjustment->bill?->bill_number ?? '') . ' (AP)';
                                }
                            @endphp
                            <tr>
                                <td class="px-4 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $approval->id }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $reference ?: '—' }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $adjustment->adjustment_number ?? '—' }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format(abs((float) ($adjustment->amount ?? 0)), 2) }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $approval->requested_at?->toDateTimeString() ?? $approval->created_at?->toDateTimeString() }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('lfs-administration.approval-workflows.credit-notes.show', $approval->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline mr-3">{{ __('Review') }}</a>
                                    <form method="POST" action="{{ route('lfs-administration.approval-workflows.credit-notes.approve', $approval->id) }}" class="inline-block mr-2">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">{{ __('Approve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('lfs-administration.approval-workflows.credit-notes.reject', $approval->id) }}" class="inline-block">
                                        @csrf
                                        <div class="hidden">
                                            <input type="text" name="comments" value="" />
                                        </div>
                                        <button type="submit" class="inline-flex items-center px-3 py-1 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700">{{ __('Reject') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('No pending credit note approvals.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($approvals->hasPages())
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                    {{ $approvals->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

