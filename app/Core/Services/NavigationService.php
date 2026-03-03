<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class NavigationService
{
    /**
     * Get navigation menu items filtered by permissions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMenuItems(): array
    {
        $items = Config::get('navigation.menu', []);

        return $this->filterItems($items);
    }

    /**
     * Determine if a menu item or its children is active for the current route.
     *
     * @param  array<string, mixed>  $item
     */
    public function isActive(array $item): bool
    {
        if (isset($item['route']) && request()->routeIs($item['route'])) {
            return true;
        }

        return $this->hasActiveChild($item);
    }

    /**
     * Determine if any child of the menu item is active.
     *
     * @param  array<string, mixed>  $item
     */
    public function hasActiveChild(array $item): bool
    {
        $children = $item['children'] ?? [];

        foreach ($children as $child) {
            $route = $child['route'] ?? null;

            if (! $route) {
                continue;
            }

            $navKey = $child['nav_key'] ?? null;

            if ($navKey !== null) {
                if (request()->routeIs($route) && request()->query('nav') === $navKey) {
                    return true;
                }
            } else {
                if (request()->routeIs($route)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function filterItems(array $items): array
    {
        $user = Auth::user();

        $filtered = [];

        foreach ($items as $item) {
            $permission = $item['permission'] ?? null;

            if ($permission && (! $user || ! $user->can($permission))) {
                continue;
            }

            $children = $item['children'] ?? [];

            if (! empty($children)) {
                $item['children'] = $this->filterItems($children);

                if (empty($item['children']) && ! isset($item['route'])) {
                    continue;
                }
            }

            $filtered[] = $item;
        }

        return $filtered;
    }
}

