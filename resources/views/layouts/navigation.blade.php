<nav x-data="{ open: false, helpOpen: false, notificationsOpen: false }" class="bg-white border-b border-gray-200">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center space-x-3">
                <button id="sidebar-toggle"
                        class="p-2 rounded-md border border-gray-300 text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        type="button"
                        title="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>

                <a href="{{ route('dashboard.index') }}" class="flex items-center space-x-2">
                    <x-application-logo class="block h-8 w-auto fill-current text-gray-800" />
                    <span class="font-semibold text-gray-800 text-sm sm:text-base">
                        {{ config('app.name', '4PL FMS') }}
                    </span>
                </a>
            </div>

            <div class="hidden sm:flex items-center space-x-4">
                <!-- Help -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="p-2 rounded-full text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-question-circle text-lg"></i>
                    </button>
                    <div x-cloak
                         x-show="open"
                         @click.outside="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-64 bg-gray-900 text-white rounded-md shadow-lg z-20">
                        <div class="px-4 py-3 border-b border-gray-700">
                            <div class="font-semibold text-sm">Help</div>
                            <div class="text-xs text-gray-300">Search and contextual help coming soon.</div>
                        </div>
                        <div class="py-2">
                            <a href="{{ route('help.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-700">
                                {{ __('Help Center') }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="relative p-2 rounded-full text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-semibold leading-none text-white bg-red-600 rounded-full hidden">
                            0
                        </span>
                    </button>
                    <div x-cloak
                         x-show="open"
                         @click.outside="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-72 bg-gray-900 text-white rounded-md shadow-lg z-20">
                        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                            <div class="font-semibold text-sm">Notifications</div>
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="text-xs text-blue-400 hover:text-blue-300">
                                    {{ __('Mark all read') }}
                                </button>
                            </form>
                        </div>
                        <div class="py-2 max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 text-sm text-gray-300">
                                {{ __('No notifications') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="flex items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Mobile hamburger for top nav (profile/help/notifications) -->
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard.index')" :active="request()->routeIs('dashboard.index')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('help.index')">
                {{ __('Help') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('notifications.index')">
                {{ __('Notifications') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

