<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit user') }}</h2>
            <a href="{{ route('lfs-administration.settings.users') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-4 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('lfs-administration.settings.users.update', $user) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4 border border-gray-200 dark:border-gray-700">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('name') border-red-500 @enderror">
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('email') border-red-500 @enderror">
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('New password') }}</label>
                <input type="password" name="password" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('password') border-red-500 @enderror" placeholder="{{ __('Leave blank to keep current') }}">
                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Confirm new password') }}</label>
                <input type="password" name="password_confirmation" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Role') }}</label>
                <select name="role" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('role') border-red-500 @enderror">
                    @foreach ($assignableRoles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $currentRoleName) === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Department') }}</label>
                <input type="text" name="department" value="{{ old('department', $user->department) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('department') border-red-500 @enderror">
                @error('department')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Position') }}</label>
                <input type="text" name="position" value="{{ old('position', $user->position) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('position') border-red-500 @enderror">
                @error('position')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if ($user->id === auth()->id())
                    <input type="hidden" name="is_active" value="1">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ __('You cannot deactivate yourself here') }})</span>
                @else
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-gray-300 dark:border-gray-600">
                    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</label>
                @endif
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Save') }}</button>
                <a href="{{ route('lfs-administration.settings.users') }}" class="inline-flex px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
