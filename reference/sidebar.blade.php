@php
    $clientUser = auth('client')->user();
@endphp

@if($clientUser)
    {{-- Client Portal sidebar for client users (does not rely on internal permissions) --}}
    @php
        $canManageUsers = $clientUser->hasPermission('manage_users');
        $portalMenu = [
            [
                'label' => 'Dashboard',
                'route' => 'client-portal.index',
                'icon' => 'fas fa-tachometer-alt',
            ],
            [
                'label' => 'Bookings & RFQs',
                'route' => 'client-portal.bookings.index',
                'icon' => 'fas fa-file-alt',
            ],
            [
                'label' => 'Track Shipment',
                'route' => 'client-portal.tracking.index',
                'icon' => 'fas fa-shipping-fast',
            ],
            [
                'label' => 'Documents',
                'route' => 'client-portal.documents.index',
                'icon' => 'fas fa-file-pdf',
            ],
            [
                'label' => 'Billing',
                'route' => 'client-portal.billing.index',
                'icon' => 'fas fa-file-invoice-dollar',
            ],
            [
                'label' => 'Support',
                'route' => 'client-portal.support.index',
                'icon' => 'fas fa-life-ring',
            ],
        ];
        if ($canManageUsers) {
            $portalMenu[] = [
                'label' => 'Team',
                'route' => 'client-portal.team.index',
                'icon' => 'fas fa-users-cog',
            ];
        }
    @endphp
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-800 text-white overflow-y-auto custom-scrollbar">
        <div class="flex flex-col h-full">
            <div class="p-4 border-b border-gray-700">
                <span class="text-lg font-semibold">{{ __('Client Portal') }}</span>
                <div class="text-xs text-gray-300 mt-1 truncate">{{ $clientUser->client?->company_name }}</div>
            </div>
            <nav class="flex-1 p-2 space-y-1">
                @foreach($portalMenu as $item)
                    @php
                        $active = request()->routeIs($item['route']);
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium {{ $active ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                        @if(isset($item['icon']))
                            <i class="{{ $item['icon'] }} w-5"></i>
                        @endif
                        <span>{{ __($item['label']) }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </aside>
@else
    @php
        $settings = \App\Models\GeneralSetting::getSettings();
        $navigationService = app(\App\Core\Services\NavigationService::class);
        $menuItems = $navigationService->getMenuItems();
        // Initialize open menus: open menus that have active children
        $initialOpenMenus = [];
        foreach ($menuItems as $item) {
            if (isset($item['children']) && $navigationService->hasActiveChild($item)) {
                $menuKey = md5($item['label'] ?? '');
                $initialOpenMenus[$menuKey] = true;
            }
        }
    @endphp
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-800 text-white overflow-y-auto custom-scrollbar"
           x-data="{ openMenus: @js($initialOpenMenus) }">
        <div class="flex flex-col h-full">
            <div class="p-4 border-b border-gray-700">
                @if($settings->logo_url ?? null)
                    <img src="{{ $settings->logo_url }}" alt="Logo" class="h-8 object-contain" />
                @endif
                <span class="text-lg font-semibold">{{ config('app.name') }}</span>
            </div>
            <nav class="flex-1 p-2 space-y-1">
                @foreach($menuItems as $item)
                    @php
                        $hasChildren = isset($item['children']) && count($item['children']) > 0;
                        $isActive = $navigationService->isActive($item);
                        $hasActiveChild = $navigationService->hasActiveChild($item);
                        $menuKey = md5($item['label'] ?? '');
                    @endphp

                    @if($hasChildren)
                        {{-- Parent menu item with children (acts as a toggle, not a link) --}}
                        <div>
                            <div class="w-full flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium {{ $isActive || $hasActiveChild ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    @if(isset($item['icon']))
                                        <i class="{{ $item['icon'] }} w-5 flex-shrink-0"></i>
                                    @endif
                                    <span>{{ __($item['label']) }}</span>
                                </div>
                                <button type="button"
                                        @click="openMenus['{{ $menuKey }}'] = !openMenus['{{ $menuKey }}']"
                                        class="p-1 rounded hover:bg-gray-600 flex-shrink-0">
                                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                                       :class="{ 'rotate-180': openMenus['{{ $menuKey }}'] }"></i>
                                </button>
                            </div>
                            <div x-show="openMenus['{{ $menuKey }}']"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 transform -translate-y-1"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform -translate-y-1"
                                 class="ml-4 mt-1 space-y-1">
                                @foreach($item['children'] as $child)
                                    @php
                                        $childIsActive = $navigationService->isActive($child);
                                        $hasChildRoute = isset($child['route']) && $child['route'];
                                        $childHref = $hasChildRoute ? route($child['route']) : 'javascript:void(0);';
                                    @endphp
                                    <a href="{{ $childHref }}"
                                       @unless($hasChildRoute) role="button" @endunless
                                       class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium {{ $childIsActive ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                                        <i class="fas fa-circle text-xs w-2"></i>
                                        <span>{{ __($child['label']) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Single menu item without children --}}
                        <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }} w-5"></i>
                            @endif
                            <span>{{ __($item['label']) }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>
    </aside>
@endif
