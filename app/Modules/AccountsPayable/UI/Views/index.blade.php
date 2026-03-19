<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Accounts Payable') }}
        </h2>
    </x-slot>
    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @isset($kpis)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                <div class="p-4 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Outstanding AP') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($kpis['total_outstanding'] ?? 0, 2) }}
                    </div>
                    <div class="mt-1 text-xs text-gray-400">{{ __('As of :date', ['date' => $kpis['as_of_date'] ?? '']) }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Days Payable Outstanding (DPO)') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($kpis['dpo'] ?? 0, 1) }}
                    </div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Average Days to Pay') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($kpis['avg_days_to_pay'] ?? 0, 1) }}
                    </div>
                </div>
            </div>
            @if(!empty($kpis['top_vendors']))
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __('Top Vendors by Outstanding AP') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="px-2 py-1">{{ __('Vendor') }}</th>
                                    <th class="px-2 py-1 text-right">{{ __('Outstanding') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($kpis['top_vendors'] as $v)
                                    <tr>
                                        <td class="px-2 py-1">
                                            {{ $v['vendor_code'] }} - {{ $v['vendor_name'] }}
                                        </td>
                                        <td class="px-2 py-1 text-right">
                                            {{ number_format($v['outstanding'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endisset
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('accounts-payable.vendors.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-truck-loading text-2xl text-blue-600 dark:text-blue-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Vendors') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Vendor master') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-payable.bills.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-file-invoice text-2xl text-green-600 dark:text-green-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Vendor Bills') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View and manage bills') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-payable.statement') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-list-alt text-2xl text-amber-600 dark:text-amber-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Statement of Account') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('By vendor') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-payable.aging') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-clock text-2xl text-orange-600 dark:text-orange-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('AP Aging') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Outstanding by age') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('accounts-payable.payments.index') }}" class="block p-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="flex items-center">
                    <i class="fas fa-money-check-alt text-2xl text-purple-600 dark:text-purple-400 mr-4"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Payments') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Record and list payments') }}</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-app-layout>
