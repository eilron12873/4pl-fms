<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Company Settings') }}</h2>
            <a href="{{ route('lfs-administration.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">{{ __('Back') }}</a>
        </div>
    </x-slot>
    <div class="py-4 max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 p-3 rounded-md bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-sm">{{ session('success') }}</div>
        @endif
        @if ($canManage)
            <form method="POST" action="{{ route('lfs-administration.settings.company.update') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Company name') }}</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('company_name') border-red-500 @enderror">
                    @error('company_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Address') }}</label>
                    <textarea name="company_address" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('company_address') border-red-500 @enderror">{{ old('company_address', $settings->company_address) }}</textarea>
                    @error('company_address')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Registration / tax ID') }}</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number', $settings->registration_number) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('registration_number') border-red-500 @enderror">
                    @error('registration_number')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Telephone') }}</label>
                        <input type="text" name="telephone_number" value="{{ old('telephone_number', $settings->telephone_number) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('telephone_number') border-red-500 @enderror">
                        @error('telephone_number')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                        <input type="email" name="email_address" value="{{ old('email_address', $settings->email_address) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('email_address') border-red-500 @enderror">
                        @error('email_address')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Website') }}</label>
                    <input type="url" name="website" placeholder="https://" value="{{ old('website', $settings->website) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('website') border-red-500 @enderror">
                    @error('website')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Company logo') }}</label>
                    @if ($settings->logo_url)
                        <div class="mb-2 flex items-center gap-3">
                            <img src="{{ $settings->logo_url }}" alt="" class="h-12 w-12 rounded-full object-cover border border-gray-200 dark:border-gray-600">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo')) class="rounded border-gray-300 dark:border-gray-600">
                                {{ __('Remove current logo') }}
                            </label>
                        </div>
                    @endif
                    <input type="file" name="company_logo" accept="image/*" class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-800 dark:file:text-gray-200 @error('company_logo') border border-red-500 rounded-md @enderror">
                    @error('company_logo')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    @error('remove_logo')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Base currency (ISO 4217)') }}</label>
                        <input type="text" name="default_currency" maxlength="3" value="{{ old('default_currency', $settings->default_currency) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm uppercase @error('default_currency') border-red-500 @enderror">
                        @error('default_currency')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Timezone') }}</label>
                        <select name="default_timezone" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('default_timezone') border-red-500 @enderror">
                            @php $tzOld = old('default_timezone', $settings->default_timezone); @endphp
                            @foreach ($timezones as $region => $identifiers)
                                <optgroup label="{{ $region }}">
                                    @foreach ($identifiers as $tz)
                                        <option value="{{ $tz }}" @selected($tzOld === $tz)>{{ $tz }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('default_timezone')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Date format') }}</label>
                    <input type="text" name="default_date_format" value="{{ old('default_date_format', $settings->default_date_format) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('default_date_format') border-red-500 @enderror">
                    @error('default_date_format')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Fiscal year start month') }}</label>
                        <input type="number" name="fiscal_year_start_month" min="1" max="12" value="{{ old('fiscal_year_start_month', $settings->fiscal_year_start_month) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('fiscal_year_start_month') border-red-500 @enderror">
                        @error('fiscal_year_start_month')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Fiscal year start day') }}</label>
                        <input type="number" name="fiscal_year_start_day" min="1" max="31" value="{{ old('fiscal_year_start_day', $settings->fiscal_year_start_day) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm @error('fiscal_year_start_day') border-red-500 @enderror">
                        @error('fiscal_year_start_day')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="inline-flex px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">{{ __('Save') }}</button>
                </div>
            </form>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-700 dark:text-gray-300 space-y-2">
                <p><span class="font-medium">{{ __('Company name') }}:</span> {{ $settings->company_name ?? '—' }}</p>
                <p><span class="font-medium">{{ __('Currency') }}:</span> {{ $settings->default_currency }}</p>
                <p><span class="font-medium">{{ __('Timezone') }}:</span> {{ $settings->default_timezone }}</p>
                <p class="text-gray-500 dark:text-gray-400 mt-4">{{ __('You do not have permission to edit company settings.') }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
