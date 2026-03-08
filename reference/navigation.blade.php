<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard.index') }}">
                        <img src="{{ asset('image/qrssmall.png') }}" alt="4PL Financial Management System" class="h-9 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard.index')" :active="request()->routeIs('dashboard.index')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Right side: Help, Notifications, User -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-1">
                <!-- Help icon -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = ! open" class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" title="{{ __('Help') }}">
                        <i class="fas fa-question-circle text-lg"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-1 w-72 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                        <div class="py-2 px-3 border-b border-gray-200">
                            <span class="text-sm font-medium text-gray-700">{{ __('Help') }}</span>
                        </div>
                        <a href="{{ route('help.index') }}" class="block py-2 px-3 text-sm text-gray-700 hover:bg-gray-100">{{ __('Help Center') }}</a>
                        <div class="py-2 px-3 border-t border-gray-100">
                            <p class="text-xs text-gray-500">{{ __('Search and contextual help coming soon.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notifications icon -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = ! open" class="relative p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" title="{{ __('Notifications') }}">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute top-1 right-1 flex h-2 w-2 hidden" id="notification-badge">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-1 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                        <div class="py-2 px-3 border-b border-gray-200 flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">{{ __('Notifications') }}</span>
                            <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800" onclick="fetch('{{ route('notifications.read-all') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(() => document.getElementById('notification-badge').classList.add('hidden'))">{{ __('Mark all read') }}</button>
                        </div>
                        <div class="max-h-64 overflow-y-auto py-2">
                            <p class="py-4 px-3 text-sm text-gray-500 text-center">{{ __('No notifications') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Dropdown -->
                @php
                    $navUser = auth('client')->user() ?? auth()->user();
                    $isClientPortalUser = $navUser && isset($navUser->client_id);
                @endphp
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ $navUser?->name ?? __('Guest') }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(auth('web')->check())
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        @endif

                        <!-- Authentication: client portal user -> client-portal logout, else web logout -->
                        @if($isClientPortalUser)
                        <form method="POST" action="{{ route('client-portal.logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                        @endif
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard.index')" :active="request()->routeIs('dashboard.index')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <a href="{{ route('help.index') }}" class="block px-4 py-2 text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">
                <i class="fas fa-question-circle mr-2"></i>{{ __('Help') }}
            </a>
            <div class="block px-4 py-2 text-base font-medium text-gray-600">
                <i class="fas fa-bell mr-2"></i>{{ __('Notifications') }}
            </div>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $navUser?->name ?? __('Guest') }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $navUser?->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if(auth('web')->check())
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                @endif

                <!-- Authentication: client portal user -> client-portal logout, else web logout -->
                @if($isClientPortalUser)
                <form method="POST" action="{{ route('client-portal.logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">
                        {{ __('Log Out') }}
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
                @endif
            </div>
        </div>
    </div>
</nav>
