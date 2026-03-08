@php
    use App\Core\ModuleManager;
    use App\Core\Services\NavigationService;
    use App\Models\GeneralSetting;

    $settings = GeneralSetting::getSettings();
    /** @var ModuleManager $moduleManager */
    $moduleManager = app(ModuleManager::class);
    $enabledModules = collect($moduleManager->getEnabledModules())
        ->filter(fn ($module) => $module->getNav() !== null)
        ->sortBy(fn ($module) => $module->getNav()['order'] ?? 100);

    /** @var NavigationService $navigationService */
    $navigationService = app(NavigationService::class);
    $coreMenuItems = $navigationService->getMenuItems();
@endphp

<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-gray-800 text-white overflow-y-auto shadow-lg z-30">
    <div class="p-4 flex items-center space-x-3 border-b border-gray-700">
        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-gray-700">
            @if ($settings->logo_url ?? false)
                <img src="{{ $settings->logo_url }}" alt="Logo" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="font-bold text-lg">4PL</span>
            @endif
        </div>
        <div>
            <div class="font-semibold text-sm">
                {{ $settings->company_name ?? '4PL FMS' }}
            </div>
            <div class="text-xs text-gray-300">
                Financial Management System
            </div>
        </div>
    </div>

    <nav class="mt-4 space-y-1 px-2">
        @php
            $coreRoutes = collect($coreMenuItems)
                ->flatMap(function ($item) {
                    $routes = [];
                    if (! empty($item['route'] ?? null)) {
                        $routes[] = $item['route'];
                    }
                    foreach ($item['children'] ?? [] as $child) {
                        if (! empty($child['route'] ?? null)) {
                            $routes[] = $child['route'];
                        }
                    }
                    return $routes;
                })
                ->unique()
                ->values()
                ->all();

            // Route prefixes already covered by config (e.g. core-accounting, fixed-assets, financial-reporting).
            // Skip module nav link if its route prefix is in this set, so we don't show duplicate top-level entries.
            $coreRoutePrefixes = collect($coreMenuItems)
                ->flatMap(function ($item) {
                    $prefixes = [];
                    foreach ($item['children'] ?? [] as $child) {
                        $r = $child['route'] ?? null;
                        if ($r && str_contains($r, '.')) {
                            $prefixes[] = explode('.', $r)[0];
                        }
                    }
                    return $prefixes;
                })
                ->unique()
                ->values()
                ->all();
        @endphp

        {{-- Core navigation items --}}
        @foreach ($coreMenuItems as $item)
            @php
                $hasChildren = ! empty($item['children'] ?? []);
                $isActive = $navigationService->isActive($item);
                $route = $item['route'] ?? null;
                $icon = $item['icon'] ?? 'fas fa-circle';
                $label = $item['label'] ?? '';
            @endphp

            @if ($hasChildren)
                <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }" class="space-y-1">
                    <button
                        type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-200 hover:bg-gray-700 hover:text-white' }}">
                        <span class="flex items-center">
                            <i class="{{ $icon }} mr-3 w-4"></i>
                            <span>{{ __($label) }}</span>
                        </span>

                        <svg class="h-3 w-3 transform transition-transform duration-150"
                             :class="{ 'rotate-90': open }"
                             xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 20 20"
                             fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M6.293 4.293a1 1 0 011.414 0L13 9.586l-5.293 5.293a1 1 0 01-1.414-1.414L10.172 9.5 6.293 5.707a1 1 0 010-1.414z"
                                  clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="open" x-cloak class="ml-8 space-y-1">
                        @foreach ($item['children'] as $child)
                            @php
                                $childRoute = $child['route'] ?? null;
                                $childIcon = $child['icon'] ?? 'fas fa-circle';
                                $childLabel = $child['label'] ?? '';
                                $navKey = $child['nav_key'] ?? null;

                                if ($childRoute) {
                                    if ($navKey) {
                                        $childActive = request()->routeIs($childRoute) && request()->query('nav') === $navKey;
                                    } else {
                                        $childActive = request()->routeIs($childRoute);
                                    }
                                } else {
                                    $childActive = false;
                                }

                                $url = $childRoute ? route($childRoute) : null;

                                if ($url && $navKey) {
                                    $url .= (str_contains($url, '?') ? '&' : '?') . 'nav=' . urlencode($navKey);
                                }
                            @endphp

                            @if ($childRoute)
                                <a href="{{ $url }}"
                                   class="flex items-center px-3 py-1.5 text-sm rounded-md {{ $childActive ? 'bg-blue-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                    <i class="{{ $childIcon }} mr-3 w-4"></i>
                                    <span>{{ __($childLabel) }}</span>
                                </a>
                            @else
                                <div class="flex items-center px-3 py-1.5 text-sm text-gray-400">
                                    <i class="{{ $childIcon }} mr-3 w-4"></i>
                                    <span>{{ __($childLabel) }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @elseif ($route)
                <a href="{{ route($route) }}"
                   class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-200 hover:bg-gray-700 hover:text-white' }}">
                    <i class="{{ $icon }} mr-3 w-4"></i>
                    <span>{{ __($label) }}</span>
                </a>
            @endif
        @endforeach

        {{-- Generic module links --}}
        @foreach ($enabledModules as $module)
            @php
                $nav = $module->getNav();
                $permissions = $module->getPermissions();
                $permission = $permissions[0] ?? null;
                $route = $nav['route'] ?? null;
                $icon = $nav['icon'] ?? 'fas fa-circle';
                $label = $nav['label'] ?? ucfirst($module->getSlug());
                $routePrefix = $route ? explode('.', $route)[0] : null;
                $isActive = $routePrefix && request()->routeIs($routePrefix . '.*');
            @endphp

            @if ($route && in_array($route, $coreRoutes, true))
                @continue
            @endif
            @php
                $moduleRoutePrefix = $route && str_contains($route, '.') ? explode('.', $route)[0] : null;
            @endphp
            @if ($moduleRoutePrefix && in_array($moduleRoutePrefix, $coreRoutePrefixes, true))
                @continue
            @endif

            @if ($permission === null || auth()->user()?->can($permission))
                <a href="{{ $route ? route($route) : '#' }}"
                   class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-200 hover:bg-gray-700 hover:text-white' }}">
                    <i class="{{ $icon }} mr-3 w-4"></i>
                    <span>{{ __($label) }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>

