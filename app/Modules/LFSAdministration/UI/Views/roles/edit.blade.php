<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit role') }}: {{ $role->name }}</h2>
            <a href="{{ route('lfs-administration.roles') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back to Roles') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('lfs-administration.roles.update', $role->id) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            @csrf
            @method('PUT')
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('Select permissions to assign to this role.') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-96 overflow-y-auto">
                @foreach($permissions as $p)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}" {{ in_array($p->id, $rolePermissionIds) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-gray-900 dark:text-gray-100">{{ $p->name }}</span>
                    </label>
                @endforeach
            </div>
            <div class="mt-6 flex gap-2">
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Save') }}</button>
                <a href="{{ route('lfs-administration.roles') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
