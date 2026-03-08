<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Role & Permission Management') }}</h2>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-3 rounded-md bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Role') }}</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Permissions') }}</th>
                            @can('lfs-administration.manage')
                                <th class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($roles as $role)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">{{ $role->permissions_count }}</td>
                                @can('lfs-administration.manage')
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('lfs-administration.roles.edit', $role->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Edit') }}</a>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
