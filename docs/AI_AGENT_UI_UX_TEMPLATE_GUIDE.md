# AI Agent UI/UX Template Guide

**Purpose:** This document provides detailed instructions for an AI coding agent to reproduce the same uniform UI and UX patterns used in this project when building or extending applications. Follow these conventions to ensure consistency across all future features and apps.

**Target stack:** Laravel 12 (PHP 8.2+), Tailwind CSS 3.1+, Alpine.js 3.14+, Vite 6.2+, Flowbite, Font Awesome 6.7+, Select2, jQuery.

> **LFS alignment note:**  
> For the **LFS (Logistics Financial System)** project, this guide defines the **shared UI/UX shell** (layouts, components, interactions). The **authoritative architecture for modules, navigation, and permissions** is described in `PROJECT_SETUP_DOCUMENTATION.md` (True Modular Architecture: `app/Core` + `app/Modules` + `ModuleManager` + `NavigationService`). All module-related examples below have been updated or should be interpreted in that context.

---

## 1. Project Overview & Technology Stack

### 1.1 Backend
- **Framework:** Laravel 12 (PHP 8.2+)
- **Auth:** Laravel Breeze with role-based permissions
- **Database:** MySQL/PostgreSQL compatible
- **PDF:** DomPDF (barryvdh/laravel-dompdf)
- **Excel:** Maatwebsite Excel 3.1
- **QR Code:** SimpleSoftwareIO Simple QR Code
- **Activity Log:** Spatie Laravel Activity Log 4.10
- **Permissions:** Spatie Laravel Permission 6.20
- **CSV:** League CSV 9.23

### 1.2 Frontend
- **CSS:** Tailwind CSS 3.1+ with `@tailwindcss/forms` and `tailwind-scrollbar`
- **JS:** Alpine.js 3.14+ (reactive components)
- **HTTP:** Axios 1.8+
- **UI:** Flowbite 3.1+, Select2 4.1, jQuery 3.7+
- **Icons:** Font Awesome 6.7+
- **Real-time:** Laravel Echo 2.0+ with Pusher.js 8.4+ (optional)

### 1.3 Build & Tooling
- **Build:** Vite 6.2+
- **CSS:** PostCSS with Autoprefixer
- **Lint:** Laravel Pint 1.13
- **Tests:** PHPUnit 11.5+

### 1.4 Key Config Files
- `composer.json` – PHP dependencies
- `package.json` – NPM dependencies
- `tailwind.config.js` – Tailwind content paths (include `./resources/views/**/*.blade.php`, Laravel pagination views, `./storage/framework/views/*.php`, and LFS module views under `./app/Modules/*/UI/Views/**/*.blade.php`) and theme (e.g. fontFamily, plugins: forms, scrollbar)
- `vite.config.js` – Vite entry points (e.g. `resources/css/app.css`, `resources/js/app.js`) via Laravel Vite plugin
- `resources/css/app.css` – Tailwind directives (`@tailwind base/components/utilities`) and custom utility classes
- `resources/js/app.js` – Main JS entry (Select2, sidebar toggles, AJAX forms, notifications)
- `config/modules.php` – Module base path and cache configuration for the LFS **ModuleManager**
- Module service providers (LFS): `app/Modules/{Name}/{Name}ServiceProvider.php` – Each module registers its own bindings/services; modules are **discovered and booted** via `app/Core/ModuleManager.php` and `App\Providers\ModulesServiceProvider`, not manually listed in `config/app.php`

### 1.5 Module Architecture Overview (LFS True Modular Architecture)

**Core Principle (LFS):** The system uses a **True modular architecture** where:

- Core lives under `app/Core/` and **never depends directly** on modules.
- Modules live under `app/Modules/{Name}/` and are **self-contained** (Domain, Application, Infrastructure, UI, routes, migrations, manifest).
- Modules can be enabled/disabled/removed without breaking the core system.

**Module Structure (LFS):**

- Location: `app/Modules/{Name}/`
- Standard LFS structure (see `PROJECT_SETUP_DOCUMENTATION.md`, Section 13.5):
  - `Domain/` – Domain entities, value objects, state machines.
  - `Application/` – Use cases / application services.
  - `Infrastructure/Models/` – Eloquent models.
  - `Infrastructure/Repositories/` – Repositories (domain ↔ persistence).
  - `UI/Controllers/` – Web controllers for this module.
  - `UI/Views/` – Blade views (module view namespace resolves here).
  - `routes.php` – Web routes for the module.
  - Optional `api.php` – API routes (e.g. financial events).
  - Optional `migrations/` – Module-specific migrations.
  - `{Name}ServiceProvider.php` – Module service provider at the root.
  - `module.json` – Manifest (name, slug, enabled, version, description, permissions, optional nav, depends).

**Module Registration & Boot (LFS):**

- `config/modules.php` defines the modules path and cache settings.
- `app/Core/ModuleManager.php`:
  - Discovers modules by scanning `app/Modules` and reading each `module.json`.
  - Creates `Module` instances implementing `ModuleInterface`.
  - Handles enabled/disabled state and dependency ordering (`depends`).
  - Loads module routes (`routes.php` / `api.php`), views (`UI/Views`), migrations (`migrations/`), and module service providers (`{Name}ServiceProvider`).
- `App\Providers\ModulesServiceProvider`:
  - Registers `ModuleManager` and `ModuleRegistry` as singletons.
  - Loads module migrations from all enabled modules.
  - Calls `$moduleManager->boot()` to register routes/views/providers.

**Module Enable/Disable (LFS):**

- Enable/disable is controlled via each module’s `module.json` (`"enabled": true|false`) and/or `config/modules.php` + optional caching.
- When a module is disabled:
  - Its routes, views, migrations, and provider are not loaded.
  - Sidebar and navigation entries that depend on it do not appear (because either:
    - `NavigationService` filters out items whose routes/permissions are unavailable, or
    - the generic module sidebar loop only iterates **enabled** modules with `nav` in their manifest).
- Core continues to function normally with **zero modules enabled**.

**Core vs Module (LFS):**

- **Core (`app/Core/` + core app):**
  - Authentication (Laravel Breeze), user management, RBAC (Spatie Permission), system configuration (`GeneralSetting`), audit (`Activity` + `Auditable`).
  - Module infrastructure: `ModuleManager`, `Module`, `ModuleInterface`, `ModuleRegistry`, `NavigationService`.
  - Core routes (`routes/web.php`, `routes/api.php`), core layouts (`resources/views/layouts/*.blade.php`), shared components.
  - **Must never import classes from `app/Modules/*`.**
- **Module (`app/Modules/{Name}/`):**
  - Optional feature sets (for WMS, LMS, or LFS domains, e.g. Receiving, Shipment, CoreAccounting, AccountsReceivable).
  - Uses core services (auth, permissions, layouts) and implements its own domain logic and UI.
  - Can depend on core contracts/interfaces and on other modules via `depends` in `module.json`.

Core layout, components, and assets remain in `resources/` and are shared across all modules; modules provide views that extend the core layout rather than replacing it.

### 1.6 Module Assets & Build Configuration (LFS)

In the LFS project, **core assets** are handled centrally and modules primarily contribute **views and route endpoints**, not their own standalone asset pipelines.

**Core Asset Locations:**
- Core CSS: `resources/css/app.css`
- Core JS: `resources/js/app.js`
- Core views: `resources/views/**/*.blade.php`

**Module Views (LFS):**
- Module views live under `app/Modules/{Name}/UI/Views/`.
- Tailwind `content` paths for LFS must include module views, for example:
  ```js
  content: [
      './resources/views/**/*.blade.php',
      './app/Modules/*/UI/Views/**/*.blade.php', // LFS module views
      './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
      './storage/framework/views/*.php',
  ]
  ```

**Vite Configuration (LFS):**
- LFS uses a **single set of entry points** for the application:
  ```js
  // vite.config.js
  export default defineConfig({
      plugins: [
          laravel({
              input: ['resources/css/app.css', 'resources/js/app.js'],
              refresh: true,
          }),
      ],
  });
  ```
- Module-specific JS/CSS should typically be imported from these core entry points (e.g. by conditionally loading module scripts inside `app.js`), rather than defining separate Vite entries per module.

**Optional: Dynamic Module Assets (Non-core / Advanced Use):**

For projects that choose to give each module its own asset files (e.g. under a separate `resources/modules/{module}/...` tree), you can extend the Vite config with dynamic discovery. Treat this as **optional** and ensure it does **not** conflict with the LFS core build:

```js
// Example only – optional pattern if you introduce per-module assets
import { glob } from 'glob';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

function discoverModuleEntries() {
    const entries = [];
    const cssFiles = glob.sync('resources/modules/*/css/*.css');
    const jsFiles = glob.sync('resources/modules/*/js/*.js');
    entries.push(...cssFiles, ...jsFiles);
    return entries;
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css',
                ...discoverModuleEntries(),
            ],
            refresh: true,
        }),
    ],
});
```

If you do not explicitly create such a `resources/modules` tree, you can ignore this advanced pattern and rely solely on the core `app.css` / `app.js` entries.

---

## 2. Design System & Visual Identity

### 2.1 Color Palette (Tailwind)

| Usage | Classes | Notes |
|-------|---------|--------|
| Sidebar background | `bg-gray-800` | Dark sidebar |
| Sidebar text | `text-white` | Nav links |
| Active nav item | `bg-blue-600` | Current route |
| Nav hover | `hover:bg-gray-700` | Sidebar items |
| Page background | `bg-gray-100` | Body/main area |
| Card/container | `bg-white` | Content cards |
| Primary actions | `bg-blue-500`, `bg-blue-600`, `hover:bg-blue-600` | Buttons, links |
| Danger/destructive | `bg-red-500`, `bg-red-600`, `hover:bg-red-700` | Delete, cancel |
| Success | `bg-green-100`, `text-green-800`, `bg-green-600` | Success messages, toasts |
| Warning | `bg-yellow-100`, `text-yellow-800`, `bg-yellow-500` | Warnings |
| Error/validation | `text-red-600`, `bg-red-100`, `border-red-500` | Errors |
| Neutral text | `text-gray-700`, `text-gray-500`, `text-gray-900` | Labels, body, headings |
| Borders | `border-gray-200`, `border-gray-300` | Dividers, inputs |

### 2.2 Typography
- **Font:** Figtree (Tailwind `font-sans`) – set in `tailwind.config.js`:
  ```js
  fontFamily: { sans: ['Figtree', ...defaultTheme.fontFamily.sans] }
  ```
- **Fallback:** System sans-serif in `resources/css/app.css` for `body`.
- **Headings:** `text-2xl font-bold` for page title; `text-lg font-semibold` for section titles.
- **Labels:** `block text-sm font-medium text-gray-700`.
- **Body:** Default sans; use `text-sm` for secondary text.

### 2.3 Spacing & Layout
- Page padding: `p-6` for main content.
- Section spacing: `mb-4`, `mb-6` between blocks.
- Form field spacing: `mb-4` per field; use `field-container` if using custom error spacing.
- Gap between buttons: `space-x-2`, `gap-4` as appropriate.
- Max width for centered content: `max-w-7xl mx-auto` where needed.

### 2.4 Icons
- **Primary:** Font Awesome 6.7+ – use `<i class="fas fa-*">` or `<i class="far fa-*">`.
- **Navigation:** Emoji + text (e.g. 📊 Dashboard, 📦 ReceivingTrans) for quick visual scanning.
- **Buttons:** Prefer Font Awesome (e.g. `fa-edit`, `fa-trash`, `fa-eye`, `fa-plus`) for actions.
- **Loading:** `fa-spinner fa-spin` for loading states.

---

## 3. Layout Architecture

### 3.1 Main Layout Structure
- **File:** `resources/views/layouts/app.blade.php` (remains in core, never overridden by modules)
- **Body:** `class="bg-gray-100 font-sans flex flex-col min-h-screen"`
- **When authenticated:**
  - Include sidebar: `@include('layouts.sidebar')` (or use dynamic menu builder)
  - Main content wrapper: `flex-1 flex flex-col min-h-screen ml-64` (256px left margin for fixed sidebar)
  - Content area: `flex-1 p-6 overflow-auto`
- **Sections:** `@yield('title')`, `@yield('styles')`, `@yield('content')`, `@yield('scripts')`
- **Important:** Modules should NOT override core layout. Modules extend core layout (`@extends('layouts.app')`), not replace it. Layout file remains in core and includes dynamic sidebar builder instead of static menu items.

### 3.2 Sidebar (Dynamic Menu System – LFS)

For LFS, the sidebar combines:

- **Core navigation** defined in `config/navigation.php`, rendered via `App\Core\Services\NavigationService`.
- **Optional module links** derived from module manifests (`module.json` with `nav`) and the LFS `ModuleManager` (generic module loop).

This ensures:

- Core UI (dashboard, settings, etc.) is always available.
- Modules appear automatically when enabled and when the current user has the required permission.

**Core Navigation via NavigationService:**

- `config/navigation.php` defines a `menu` array with items (label, route, icon, order, permission, optional children).
- `App\Core\Services\NavigationService`:
  - Reads `config/navigation.php`.
  - Filters items and children by `auth()->user()->can($permission)` (Spatie Permission).
  - Provides helpers such as `isActive()` / `hasActiveChild()`.

**Sidebar View Implementation (Core Menu):**

- **File:** `resources/views/layouts/sidebar.blade.php`
- **Container:** `fixed inset-y-0 left-0 w-64 bg-gray-800 text-white h-screen overflow-y-auto shadow-lg z-30`
- **Brand area:** Logo (if present) + system name; `p-4 flex items-center gap-3`
- **Core menu rendering (simplified example):**

```blade
@php
    $navigationService = app(\App\Core\Services\NavigationService::class);
    $menuItems = $navigationService->getMenuItems(); // already permission-filtered
@endphp

<nav>
    <ul>
        @foreach($menuItems as $item)
            @php
                $hasChildren = !empty($item['children'] ?? []);
                $isActive = $navigationService->isActive($item);
            @endphp

            <li class="p-4 hover:bg-gray-700 {{ $isActive ? 'bg-gray-700' : '' }}">
                @if($hasChildren)
                    {{-- Collapsible section --}}
                    <button id="{{ $item['id'] ?? Str::slug($item['label']) }}-toggle"
                            class="w-full text-left flex justify-between items-center">
                        <span><i class="{{ $item['icon'] ?? '' }} mr-2"></i>{{ $item['label'] }}</span>
                        <svg id="{{ $item['id'] ?? Str::slug($item['label']) }}-arrow"
                             class="w-4 h-4 transform transition-transform {{ $isActive ? 'rotate-180' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <ul id="{{ $item['id'] ?? Str::slug($item['label']) }}-menu"
                        class="ml-4 mt-2 {{ $isActive ? '' : 'hidden' }}">
                        @foreach($item['children'] as $child)
                            <li class="text-sm {{ request()->routeIs($child['route']) ? 'bg-blue-600' : '' }}">
                                <a href="{{ route($child['route']) }}" class="block p-2 hover:bg-gray-600">
                                    {{ $child['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    {{-- Single menu item --}}
                    <a href="{{ route($item['route']) }}"
                       class="block p-2 rounded {{ request()->routeIs($item['route']) ? 'bg-blue-600' : '' }}">
                        <i class="{{ $item['icon'] ?? '' }} mr-2"></i>{{ $item['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
```

This structure is compatible with the hamburger toggle and collapsible behavior described in Sections 3.3–3.4 and 6.2 (IDs following the `{key}-toggle`, `{key}-menu`, `{key}-arrow` pattern).

**Generic Module Links (LFS Modules with `nav` in module.json):**

In addition to the core menu, LFS supports a **generic module loop** that automatically adds links for enabled modules that define `nav` in their `module.json`:

- `ModuleManager::getEnabledModules()` returns all enabled modules.
- Each module exposes `getNav()` (label, route, icon, order) and `getPermissions()` (list of permission names).
- Sidebar loop:
  - Filters modules to those with `getNav() !== null`.
  - Sorts by `nav['order']`.
  - Shows a link only if `auth()->user()->can($module->getPermissions()[0])`.

This generic loop ensures that when you add a new LFS module and give it `permissions` + `nav` in `module.json`, it appears in the sidebar automatically once its permissions are seeded and assigned.

### 3.3 Header (Inside Main Content)
- **Structure:** `header class="flex justify-between items-center mb-6 relative z-10"`
- **Left side:** **Hamburger toggle button** (see 3.4) then **Title:** `<h1 class="text-2xl font-bold">@yield('title')</h1>` – use `flex items-center gap-3` for the left group
- **Right side:** Help icon (optional), Notifications (optional), User profile dropdown – `flex items-center gap-4`
- **Divider:** `<hr class="border-gray-300 mb-6 relative z-0">` below header

### 3.4 Hamburger Toggle Switch (Collapsible Left Navigation Pane)

Provide a hamburger toggle that hides and expands the left navigation pane, with state persisted in `localStorage`. Implement identically to the **4pl-wms** project for consistent UX.

**1. Sidebar element** (`resources/views/layouts/sidebar.blade.php`):
- The `<aside>` must have `id="sidebar"`.
- Keep fixed positioning and width: e.g. `fixed inset-y-0 left-0 w-64 bg-gray-800 text-white h-screen overflow-y-auto shadow-lg z-30`.

**2. Main content wrapper** (`resources/views/layouts/app.blade.php`):
- The div wrapping the main content (header + content) must have `id="main-layout"`.
- When the sidebar is visible, this div must have `ml-64` (256px left margin). When the sidebar is hidden, `ml-64` is removed so content uses full width.
- Example: `class="flex-1 flex flex-col min-h-screen ml-64"`.

**3. Toggle button in header:**
- Place at the **start** of the header (before the page title), inside a left-side group (e.g. `flex items-center gap-3`).
- **IDs and attributes:** `id="sidebar-toggle"`, `type="button"`, `title="Toggle navigation"`.
- **Icon:** Font Awesome bars: `<i class="fas fa-bars"></i>`.
- **Tailwind classes:** `p-2 rounded-md border border-gray-300 text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500`.

**4. JavaScript (in app layout, run on DOMContentLoaded):**
- **Elements:** `sidebar = document.getElementById('sidebar')`, `mainLayout = document.getElementById('main-layout')`, `toggleBtn = document.getElementById('sidebar-toggle')`.
- **State:** `let sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === '1';`
- **applySidebarState():**
  - If `sidebarCollapsed`: add `hidden` to sidebar; remove `ml-64` from main layout.
  - Else: remove `hidden` from sidebar; add `ml-64` to main layout if not present.
- **On load:** call `applySidebarState()` so initial state matches saved preference.
- **On toggle click:** set `sidebarCollapsed = !sidebarCollapsed`, `localStorage.setItem('sidebar-collapsed', sidebarCollapsed ? '1' : '0')`, then `applySidebarState()`.

**Result:** One click hides the sidebar and expands content; another click restores the sidebar; preference is remembered across page loads (same behavior as 4pl-wms).

### 3.5 Footer
- **Container:** `footer class="bg-gray-800 text-white mt-auto"`
- **Content:** `max-w-7xl mx-auto px-6 py-6` with grid for company info, contact, quick links, system info
- **Copyright:** `border-t border-gray-700 mt-6 pt-6 text-center` with `text-gray-400 text-sm`

### 3.6 Page View Structure

**Core Views:**
- Location: `resources/views/`
- Every authenticated page view MUST:
  1. `@extends('layouts.app')`
  2. `@section('title', 'Page Title')`
  3. Put main content in `@section('content')`
  4. Use a wrapper such as `container mx-auto p-6 bg-white rounded-lg shadow-md` for the main card when appropriate

**Module Views:**
- Location: `modules/{ModuleName}/resources/views/`
- Module views MUST also extend core layout: `@extends('layouts.app')`
- View namespace registration: In module service provider, register view namespace:
  ```php
  $this->loadViewsFrom(__DIR__.'/resources/views', 'module-name');
  ```
- Referencing module views:
  - From controllers: `return view('module-name::view-name', $data);`
  - From other views: Use `@include('module-name::view-name')` or return from controller
- View precedence: Core views take precedence over module views (or vice versa, depending on registration order). Document your convention clearly.

---

## 4. Component Library

### 4.1 Blade Components Location

**Core Components:**
- Directory: `resources/views/components/`
- Use with `<x-component-name />` or `<x-component-name>slot</x-component-name>`

**Module Components:**
- Directory: `modules/{ModuleName}/resources/views/components/`
- Component namespace registration: In module service provider:
  ```php
  $this->loadViewComponentsAs('module-name', [
      'component-name' => \Modules\ModuleName\View\Components\ComponentName::class,
  ]);
  ```
- Referencing module components: `<x-module-name::component-name />` or `<x-module-name::component-name>slot</x-module-name::component-name>`
- Component resolution: Core components override module components (or vice versa, depending on convention). Document your resolution strategy.

**Component Patterns:**
- Module components should follow the same patterns as core components (Section 4.2-4.7)
- Use same Tailwind classes, same prop patterns, same accessibility considerations

### 4.2 Buttons
- **Primary (submit/secondary):** `resources/views/components/primary-button.blade.php`
  - Classes: `inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150`
- **Danger:** `resources/views/components/danger-button.blade.php`
  - Classes: same pattern but `bg-red-600 hover:bg-red-500 active:bg-red-700 focus:ring-red-500`
- **Secondary:** `resources/views/components/secondary-button.blade.php` – use for cancel/secondary actions.
- **Inline convention:** For simple links styled as buttons use: `bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md` (primary) or `bg-red-600 hover:bg-red-700` (danger).

### 4.3 Form Components
- **Label:** `<x-input-label value="Label" />` or with slot; class `block font-medium text-sm text-gray-700`
- **Text input:** `<x-text-input ... />` – merge classes: `border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm`
- **Error display:** `<x-input-error :messages="$errors->get('field')" />` – class `text-sm text-red-600 space-y-1`
- **Manual pattern when not using components:**
  ```blade
  <div class="mb-4">
      <label for="field" class="block text-sm font-medium text-gray-700">Label</label>
      <input type="text" id="field" name="field" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" />
      @error('field')
          <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
      @enderror
  </div>
  ```

### 4.4 Modals
- **Alpine/Blade modal:** `resources/views/components/modal.blade.php`
  - Props: `name`, `show` (default false), `maxWidth` ('sm'|'md'|'lg'|'xl'|'2xl')
  - Open/close: `$dispatch('open-modal', 'modalName')` / `$dispatch('close-modal', 'modalName')`
  - Backdrop: `bg-gray-500 opacity-75`; panel: `bg-white rounded-lg overflow-hidden shadow-xl`
- **Transaction/reason modal:** `resources/views/components/transaction-action-modal.blade.php`
  - Props: `modalId`, `title`, `actionUrl`, `buttonText`, `reasonLabel`, `placeholder`, `warningMessage`, `color` ('blue'|'red'|'orange'), optional `showTypeSelect`
  - Uses global JS: `closeTransactionModal(modalId)`, `handleTransactionSubmit(modalId, actionUrl, buttonText)` (defined in `app.blade.php`)
  - Ensure page has CSRF meta: `<meta name="csrf-token" content="{{ csrf_token() }}">`

### 4.5 Dropdowns
- **Dropdown:** `resources/views/components/dropdown.blade.php` and `dropdown-link.blade.php` for aligned menu styling.
- **Select2:** Use class `select2` on `<select>`; initialize in `app.js` with `width: '100%'`, `placeholder`, `allowClear: true`. Style Select2 to match Tailwind (e.g. border `#d1d5db`, focus `#3b82f6`) in `@section('styles')` if needed.

### 4.6 Badges & Status
- **Status badges:** Use Tailwind rounded badges, e.g.:
  - Success: `px-2 py-0.5 text-xs rounded bg-green-100 text-green-800`
  - Warning: `bg-yellow-100 text-yellow-800`
  - Danger: `bg-red-100 text-red-800`
  - Info/neutral: `bg-blue-100 text-blue-700` or `bg-gray-100 text-gray-700`
- **Priority (example):** Red (80–100), Yellow (50–79), Green (0–49) with same pattern as above.
- Use Font Awesome icon next to badge when it helps (e.g. `fa-check`, `fa-exclamation-triangle`).

### 4.7 Tables
- **Container:** `overflow-x-auto` for horizontal scroll on small screens.
- **Table:** `min-w-full divide-y divide-gray-200`; header `bg-gray-50`; cells `px-4 py-3 text-sm text-gray-700` (or `text-gray-500` for secondary).
- **Actions column:** Icon buttons (e.g. edit, view, delete) with `text-blue-600 hover:text-blue-800`, `text-yellow-600`, `text-red-600` and title attributes for accessibility.
- **Pagination:** Use Laravel paginator: `{{ $records->links() }}` (ensure Tailwind pagination views are published/configured if custom styling is required).

---

## 5. UI Patterns

### 5.1 Flash Messages
Always display session flash at the top of the content section (after header, before main content):

```blade
@if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-800 rounded flex justify-between items-center" role="alert">
        <span>{{ session('success') }}</span>
        <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()" aria-label="Dismiss">×</button>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded flex justify-between items-center" role="alert">
        <span>{{ session('error') }}</span>
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()" aria-label="Dismiss">×</button>
    </div>
@endif

@if($errors->any())
    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="mt-2 text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">Dismiss</button>
    </div>
@endif
```

- Controllers should use `redirect()->back()->with('success', '...')` or `->with('error', '...')` and validation will auto-populate `$errors`.

### 5.2 Form Validation (Server-Side)
- Use Laravel `$request->validate([...])` in controllers.
- In Blade: `@error('field')` / `@enderror` or `<x-input-error :messages="$errors->get('field')" />` next to each field.
- For AJAX: return JSON with `errors` key and display in UI (e.g. under fields or in a single alert).

### 5.3 Loading States
- Buttons: On submit, disable button and set content to e.g. `<i class="fas fa-spinner fa-spin mr-2"></i> Processing...`
- Full page: Optional `#loading-spinner` that is hidden on `DOMContentLoaded` (see `app.blade.php`).
- Alpine: Use `x-data` with a `loading` property and `x-show="loading"` for spinners.

### 5.4 Empty States
- When a list/table has no results: show a centered message, e.g. `text-gray-500 text-center py-8` with an icon (e.g. `fa-inbox`) and short message like "No records found."

### 5.5 Search & Filter
- Place search/filter above the table in a card or toolbar.
- Search input: `border border-gray-300 rounded-md` with `id="search-input"` if using the global table search in `app.js` (optional). Prefer debounce (e.g. 300ms) for client-side filtering.
- Filters: Use form with GET method and standard Tailwind form controls; submit on change or with a "Filter" button.

### 5.6 Breadcrumbs
- When used: place below page title; use `text-sm` and links with `text-blue-600 hover:text-blue-800`; current page as plain text.

### 5.7 Confirmation for Destructive Actions
- Always use a confirmation step: either Flowbite modal or the transaction-action-modal (when a reason is required). Never delete on a single click without confirmation.

---

## 6. JavaScript Conventions

### 6.1 Alpine.js
- Use for: dropdowns, modals, tabs, inline toggles, and small reactive UI pieces.
- Load: `defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"` (or via Vite bundle if preferred).
- Hide until ready: `[x-cloak] { display: none !important; }` in layout.
- Prefer `x-data`, `x-show`, `x-on:click`, `@click.away` for dropdowns; use `x-transition` for smooth open/close.

### 6.2 Global Scripts (app.js)
- **Select2:** Initialize `.select2()` on `.select2` elements with `width: '100%'`, `placeholder`, `allowClear: true`.
- **Sidebar toggles:** Match toggle button id `{section}-toggle`, menu id `{section}-menu`, arrow id `{section}-arrow`. Toggle `hidden` on menu and `rotate-180` on arrow. Expand by default when current path matches section paths. Works with dynamic menu structure from MenuRegistry.
- **Settings group toggles:** `.settings-group-toggle` with `data-group`; corresponding `.settings-group[data-group="..."]`; toggle `hidden` and arrow `rotate-90`.
- **Sidebar scroll:** Persist scroll position in `localStorage` key `sidebar-scroll-top` on scroll and beforeunload; restore on load.
- **AJAX forms:** For forms with `data-ajax`, prevent default submit, use `fetch` with `X-CSRF-TOKEN` and `Accept: application/json`, send FormData; on success show notification and optional redirect; on error show notification from `data.errors.general` or message.
- **Notifications:** Implement a small toast (e.g. fixed top-right, `bg-green-600` or `bg-red-600`, auto-remove after 3s) and use it from AJAX handlers and global error handler.

**Module-Specific JavaScript:**
- Module JS files are loaded conditionally based on enabled modules (via Vite dynamic entry discovery)
- Modules can register initialization functions that run on `DOMContentLoaded`:
  ```js
  // In module JS file
  document.addEventListener('DOMContentLoaded', () => {
      // Module-specific initialization
      initializeModuleFeatures();
  });
  ```
- Module JS should follow same patterns as core JS (Alpine.js, CSRF handling, etc.)
- Avoid global namespace pollution; use module-specific prefixes or namespaces if needed

### 6.3 CSRF and AJAX
- Include `<meta name="csrf-token" content="{{ csrf_token() }}">` in layout head.
- All POST/PUT/DELETE requests: add header `X-CSRF-TOKEN: document.querySelector('meta[name="csrf-token"]').content`.
- For JSON body use `Content-Type: application/json` and send JSON; for FormData do not set Content-Type (browser sets multipart boundary).

### 6.4 jQuery
- Used for Select2 and any legacy code. Prefer vanilla JS or Alpine for new code when possible. Ensure jQuery is loaded before Select2 and before any script that depends on it.

### 6.5 Flowbite
- Load Flowbite CSS and JS as in layout (CDN or build). Call `initFlowbite()` on `DOMContentLoaded` if required for dynamic components. Use Flowbite for modals, dropdowns, tabs when not using custom Alpine components.

---

## 7. Navigation & Permissions (LFS)

### 7.1 Permission Structure (Spatie Permission)

- LFS uses **Spatie Laravel Permission**:
  - Permissions are stored in the `permissions` table.
  - Roles are stored in the `roles` table, with pivot tables linking users ↔ roles ↔ permissions.
- Check permissions using:
  - `auth()->user()->can('permission-name')` or
  - `auth()->user()->hasRole('Role Name')`.
- In Blade:
  - `@can('permission-name') ... @endcan`
  - `@role('Role Name') ... @endrole`

The sidebar and other UI elements should rely on `can()` / `@can` for visibility, not JSON fields on the role model.

### 7.2 Active State

- Exact route: `Route::currentRouteName() === 'route.name' ? 'bg-blue-600' : ''`
- Section (prefix / wildcard): `request()->routeIs('module.*') ? 'bg-gray-700' : ''` for parent, and `bg-blue-600` for the matching child link.
- `NavigationService` can encapsulate the active-state logic with helpers like `isActive()` / `hasActiveChild()`, and views should use those helpers where possible.

### 7.3 Collapsible Menus

- Parent button: `<button id="section-toggle">` with text + arrow SVG.
- Child list: `<ul id="section-menu" class="... hidden">`.
- In `app.js`, on toggle click: menu `classList.toggle('hidden')`, arrow `classList.toggle('rotate-180')`.
- On load, if the current route belongs to the section (e.g. `request()->routeIs('section.*')`), remove `hidden` and add `rotate-180` so the section is expanded.
- ID pattern: `{key}-toggle`, `{key}-menu`, `{key}-arrow` so the JavaScript in Section 6.2 can work with any sidebar structure.

### 7.4 Module Permission Registration (LFS)

**Permission Naming Convention:**

- Format: `{module-slug}.{action}` (e.g. `shipment.view`, `core-accounting.view`, `accounts-receivable.manage`).
- `{module-slug}` should match the module manifest’s `slug` in `module.json` and the permission names returned by `$module->getPermissions()`.
- Use consistent action names, such as:
  - `view`, `create`, `edit`, `update`, `delete`, `approve`, etc.

**Where Permissions Come From (LFS):**

- Each module’s `module.json` can declare a `permissions` array, e.g.:
  ```json
  {
      "name": "Shipment",
      "slug": "shipment",
      "enabled": true,
      "version": "1.0.0",
      "description": "Shipment management module",
      "permissions": ["shipment.view"]
  }
  ```
- `app/Core/Module.php` exposes these via `getPermissions(): array`.
- `database/seeders/ModulePermissionsSeeder.php`:
  - Calls `app(\App\Core\ModuleManager::class)->getEnabledModules()`.
  - Collects each module’s `$module->getPermissions()`.
  - Optionally merges in a list of **core permissions** (non-module).
  - Creates each permission in the database (guard `web`).
  - Assigns all permissions to the **Super Admin** role.

**Permission Checking (LFS):**

- Always ensure the module is enabled **and** the user has the relevant permission:
  ```php
  /** @var \App\Core\ModuleManager $moduleManager */
  $moduleManager = app(\App\Core\ModuleManager::class);

  if ($moduleManager->isEnabled('shipment') &&
      auth()->user()?->can('shipment.view')) {
      // Show menu item or allow action
  }
  ```

- In Blade:
  ```blade
  @php
      $moduleManager = app(\App\Core\ModuleManager::class);
  @endphp

  @if($moduleManager->isEnabled('shipment') && auth()->user()?->can('shipment.view'))
      {{-- Show Shipment link --}}
  @endif
  ```

**Core Permissions:**

- Core system permissions (e.g. dashboard, settings, user management) can be defined as a simple array inside `ModulePermissionsSeeder` (e.g. `$corePermissions = ['dashboard.view', 'settings.manage']`) and seeded alongside module permissions.
- Core permissions work independently of modules and should also be checked with `auth()->user()->can(...)` / `@can`.

---

## 8. Code Conventions

### 8.1 File Naming
- Blade: `kebab-case.blade.php` (e.g. `stock-on-hand.blade.php`).
- Controllers: PascalCase, suffix `Controller` (e.g. `OrderingController.php`).
- JS/CSS: kebab-case or camelCase as per existing project (e.g. `app.js`, `multi-item-bin-display.js`).

**Module File Naming:**
- Module directory: PascalCase (e.g. `modules/Receiving/`, `modules/Picking/`)
- Module service provider: `{ModuleName}ServiceProvider.php` (e.g. `ReceivingServiceProvider.php`)
- Module config/metadata: `module.json` or `config/module.php` (contains module name, version, dependencies, etc.)
- Module routes: `routes/web.php` (within module directory)
- Module migrations: `database/migrations/` (within module directory, use module prefix in migration names)

### 8.2 Blade
- Use `@extends`, `@section`, `@yield` for layout.
- Use `@auth` / `@guest` for auth-specific blocks.
- Prefer `<x-*>` components for repeated UI.
- Escape output with `{{ }}`; use `{!! !!}` only when safe HTML is intentional.
- Use `@php` blocks sparingly; keep logic in controllers or view composers when possible.

### 8.3 CSS
- Prefer Tailwind utility classes in Blade/HTML.
- Custom classes in `resources/css/app.css`; use `@apply` for component-like reuse (e.g. `.dropdown-menu`, `.error-message`).
- Avoid inline styles except for dynamic values (e.g. widths from backend).

### 8.4 Responsive
- Use Tailwind breakpoints: `sm:`, `md:`, `lg:` where layout or visibility changes. Sidebar is fixed; main content has `ml-64`; consider hiding sidebar or switching to drawer on very small screens if required later.

---

## 9. Implementation Checklist for New Apps/Features

Use this when creating a new app or feature that must match this UI/UX:

1. **Dependencies**
   - [ ] Laravel 12, PHP 8.2+
   - [ ] Tailwind CSS 3.1+ with `@tailwindcss/forms` and `tailwind-scrollbar`
   - [ ] Alpine.js 3.14+, Flowbite 3.x, Font Awesome 6.7+, Select2 4.x, jQuery 3.x, Axios
   - [ ] Vite 6.x with Laravel plugin; entry points for `resources/css/app.css` and `resources/js/app.js`

2. **Layout**
   - [ ] Copy/adapt `resources/views/layouts/app.blade.php` (head: CSRF meta, Vite, Font Awesome, Select2, Flowbite, Alpine, Quagga/jsQR only if needed).
   - [ ] Copy/adapt `resources/views/layouts/sidebar.blade.php` with permission-based menu and collapsible sections; sidebar `<aside>` must have `id="sidebar"`.
   - [ ] Ensure body has `bg-gray-100 font-sans flex flex-col min-h-screen`; main content wrapper has `id="main-layout"` and `ml-64` when sidebar is visible.
   - [ ] Add **Hamburger Toggle Switch** for left navigation pane (Section 3.4): toggle button in header, JS for hide/expand with `localStorage` persistence.

3. **Design tokens**
   - [ ] Tailwind: extend `fontFamily` with Figtree; keep default gray/blue/red/green/yellow palette.
   - [ ] Use color scheme from Section 2.1 for sidebar, active, hover, buttons, messages.

4. **Components**
   - [ ] Provide Blade components: primary-button, danger-button, secondary-button, input-label, text-input, input-error, modal, transaction-action-modal (if needed), dropdown/dropdown-link.
   - [ ] Use Flash message block (Section 5.1) on every page that can receive redirects with success/error.

5. **JS**
   - [ ] app.js (or inline in layout): Select2 init, **hamburger sidebar toggle** (Section 3.4), settings-group toggles, sidebar scroll persistence, optional data-ajax form handler, showNotification (toast), global error handler.
   - [ ] Define closeTransactionModal and handleTransactionSubmit in layout if using transaction-action-modal.

6. **Forms**
   - [ ] Server-side validation; display errors with @error or input-error component.
   - [ ] Use Tailwind form classes and Flowbite where applicable; style Select2 to match.

7. **Tables & lists**
   - [ ] Pagination with `$records->links()`; overflow-x-auto; action icons with consistent colors (blue edit, red delete, etc.).
   - [ ] Empty state when no records.

8. **Modals**
   - [ ] Use Flowbite or Alpine modal component; for destructive/reason-required actions use transaction-action-modal pattern with CSRF and JSON response (success, redirect, errors).

9. **Navigation**
   - [ ] Permissions from role (JSON); show/hide menu items by permission; active state by route name; collapsible sections with persisted open state by current route.

10. **Testing**
    - [ ] After implementing, verify: flash messages appear, validation errors show, modals open/close, sidebar expands for current section, Select2 works, AJAX forms submit with CSRF and show notification.

**For Modular Architecture (Additional Checklist – LFS):**

11. **Module Service Provider & Manifest**
    - [ ] Each LFS module has `app/Modules/{Name}/{Name}ServiceProvider.php` and `app/Modules/{Name}/module.json`.
    - [ ] `module.json` contains at least: `name`, `slug`, `enabled`, `version`, `description`, `permissions` (and optional `nav`, `depends`).
    - [ ] Module service provider registers any module-specific bindings/services; **routes, views, and migrations are loaded via `ModuleManager`** (not manually from core).

12. **Module Views & Components**
    - [ ] Module views live under `app/Modules/{Name}/UI/Views/` and extend core layout (`@extends('layouts.app')` or `<x-app-layout>`).
    - [ ] Module views are reachable via the module’s view namespace (e.g. `view('shipment::index')`) based on how `ModuleManager` registers them.
    - [ ] Module components (if any) follow core component patterns (same Tailwind classes and props).

13. **Module Assets**
    - [ ] Tailwind `content` paths include module views (e.g. `./app/Modules/*/UI/Views/**/*.blade.php`).
    - [ ] Module-specific JS/CSS is imported via core `resources/js/app.js` / `resources/css/app.css` (or via the optional dynamic pattern described in Section 1.6), not via a separate, conflicting build.

14. **Module Routes & Permissions**
    - [ ] Module web routes live in `app/Modules/{Name}/routes.php`, use consistent naming (`{slug}.index`, `{slug}.show`, etc.), and have appropriate middleware (`auth`, `verified`, `permission:{slug}.view`).
    - [ ] Module API routes (if any) live in `app/Modules/{Name}/api.php`, use `auth:sanctum`, and are prefixed with the module slug.
    - [ ] Module permissions follow the `{module-slug}.{action}` convention and are declared in `module.json` → `permissions`.
    - [ ] `ModulePermissionsSeeder` collects permissions from all **enabled** modules and seeds them (plus any core permissions) into Spatie Permission.

15. **Module Menu Integration**
    - [ ] Core navigation is defined in `config/navigation.php` and rendered via `NavigationService`.
    - [ ] Optional generic module links in the sidebar are derived from enabled modules with `nav` in `module.json` (label, route, icon, order).
    - [ ] Sidebar only shows module links when the module is enabled and the user `can()` at least the module’s primary permission.

16. **Module Isolation**
    - [ ] A module can be disabled (`"enabled": false` in `module.json`) without breaking core routes, layouts, or other modules.
    - [ ] Module dependencies are declared in `module.json` (`depends`) and enforced by `ModuleManager` (modules with unmet dependencies are not booted).
    - [ ] Modules do not modify core files; they interact with core via public contracts/services (ModuleManager, NavigationService, Spatie Permission, etc.).

---

## 10. Quick Reference: Class Snippets

- **Page container:** `container mx-auto p-6 bg-white rounded-lg shadow-md`
- **Card:** `bg-white rounded-lg shadow-md p-6` or `border border-gray-200 rounded-lg p-4`
- **Primary button:** `bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium`
- **Danger button:** `bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md`
- **Input:** `mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500`
- **Label:** `block text-sm font-medium text-gray-700`
- **Error text:** `mt-1 text-sm text-red-600`
- **Success alert:** `p-4 bg-green-100 text-green-800 rounded` or with `border-l-4 border-green-500`
- **Error alert:** `p-4 bg-red-100 text-red-800 rounded` or with `border-l-4 border-red-500`
- **Badge:** `px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-700`
- **Icon button:** `p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full`
- **Table header:** `bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase`
- **Table cell:** `px-4 py-3 text-sm text-gray-700` or `text-gray-500`

**Module References:**
- **Module view:** `module-name::view-name` (e.g., `receiving::asns.index`)
- **Module component:** `<x-module-name::component-name />` (e.g., `<x-receiving::asn-card />`)
- **Module route:** `route('module-name.action')` (e.g., `route('receiving.asns.index')`)

---

## 11. Related Documentation

- **Project context and domain:** [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) – WMS domain, warehouse hierarchy, multi-tenancy, SKU conventions, and additional UI/UX notes.
- **Features and modules:** [4PL_WMS_FEATURES_DOCUMENTATION.md](4PL_WMS_FEATURES_DOCUMENTATION.md) – High-level feature list and module descriptions.
- **Implementation examples:** Module-specific docs (e.g. BIN_RULES_UI_DOCUMENTATION.md, ROLE_MANAGER_DOCUMENTATION.md) for detailed UI/UX examples within this project.

---

## 12. Module Development Guidelines (LFS)

**Module Directory Structure (LFS):**
```text
app/Modules/{Name}/
├── Domain/                    # Domain entities, value objects, state machines
│   └── {Name}.php
├── Application/               # Use cases / application services
│   └── {Name}Overview.php
├── Infrastructure/
│   ├── Models/
│   │   └── {Name}Model.php
│   └── Repositories/
│       └── {Name}Repository.php
├── UI/
│   ├── Controllers/
│   │   └── {Name}Controller.php
│   └── Views/
│       └── index.blade.php
├── routes.php                 # Web routes
├── api.php                    # API routes (optional)
├── migrations/                # Module-specific migrations (optional)
├── {Name}ServiceProvider.php  # Module service provider (root)
└── module.json                # Manifest: name, slug, enabled, version, description, permissions, nav, depends
```

**Required Files (LFS):**

1. **{Name}ServiceProvider.php:**
   ```php
   <?php

   namespace App\Modules\{Name};

   use Illuminate\Support\ServiceProvider;

   class {Name}ServiceProvider extends ServiceProvider
   {
       public function register(): void
       {
           // Register bindings (repositories, services) for this module
       }

       public function boot(): void
       {
           // Optional: any module-specific boot logic
           // Routes, views, migrations are loaded via App\Core\ModuleManager
       }
   }
   ```

2. **module.json (LFS-style manifest):**
   ```json
   {
       "name": "{Name}",
       "slug": "{kebab-slug}",
       "enabled": true,
       "version": "1.0.0",
       "description": "{Name} module",
       "permissions": [
           "{kebab-slug}.view"
       ],
       "nav": {
           "label": "{Name}",
           "route": "{kebab-slug}.index",
           "icon": "fas fa-chart-line",
           "order": 100
       },
       "depends": []
   }
   ```

**Module Naming Conventions (LFS):**
- Module directory: PascalCase (e.g., `Shipment`, `CoreAccounting`).
- Slug (in `module.json`): kebab-case (e.g., `shipment`, `core-accounting`).
- Service provider: `{Name}ServiceProvider.php` in `app/Modules/{Name}/`.
- Route names: `{slug}.index`, `{slug}.show`, `{slug}.edit`, etc.

**Creating a New Module (LFS):**

1. Create module directory structure under `app/Modules/{Name}/` using the DDD layout above.
2. Create `{Name}ServiceProvider.php` at module root (namespace `App\Modules\{Name}`).
3. Create `module.json` with manifest data (name, slug, enabled, version, description, permissions, optional nav, depends).
4. Add `routes.php` with web routes, using:
   - `middleware(['auth','verified','permission:{slug}.view'])`
   - `prefix('{slug}')`
   - `name('{slug}.')`
5. Optionally add `api.php` for API routes with `middleware('auth:sanctum')`, prefixed with `{slug}` and named `api.{slug}.`.
6. Create `UI/Controllers/{Name}Controller.php` with at least an `index()` action returning `view('{slug}::index')`.
7. Create `UI/Views/index.blade.php` that extends the core layout and follows the UI/UX patterns in this guide.
8. Add any required Domain/Application/Infrastructure classes.
9. Add migrations under `migrations/` when the module needs its own tables.
10. Run migrations and `php artisan db:seed` (ModulePermissionsSeeder) so the module’s permissions exist and can be assigned.

**Module Dependencies (LFS):**
- Declare dependencies in `module.json` under `depends`, e.g.:
  ```json
  "depends": ["core-accounting", "general-ledger"]
  ```
- `ModuleManager` reads `depends` and loads modules in dependency order; modules with unmet dependencies are not booted.

**Module Views:**
- MUST use the shared layouts and components:
  - Either `@extends('layouts.app')` + `@section('content')`, or
  - `<x-app-layout>` with slots/content.
- Use the same UI/UX patterns as core (flash messages, form validation, modals, tables, etc.).
- Refer to module views via their namespace (e.g. `return view('shipment::index')`) as registered by `ModuleManager`.

**Module Routes:**
- Use consistent route names built from the module slug (e.g., `shipment.index`, `shipment.show`).
- Apply appropriate middleware:
  - `auth` and `verified` for web.
  - `auth:sanctum` for API.
  - `permission:{slug}.view` (and other actions as needed) using Spatie Permission.

**Module Migrations:**
- Place migrations under `app/Modules/{Name}/migrations/`.
- `App\Providers\ModulesServiceProvider` calls `$moduleManager->getMigrationPaths()` and registers them via `$this->loadMigrationsFrom($path)`.
- Use clear table names and indexes that are coherent with the domain (e.g. `shipments`, `journals`, `invoices`).

**Module Assets:**
- Prefer using the core asset pipeline:
  - Import module-specific JS/CSS inside `resources/js/app.js` / `resources/css/app.css`.
- If you introduce a separate `resources/modules/{module}` tree for assets, follow the optional pattern in Section 1.6 and keep it consistent with the core build.

**Module Isolation (LFS):**
- Modules should NOT modify core files.
- Core (`app/Core/` + core app) must not import module classes.
- Modules interact with core via:
  - `ModuleManager` / `ModuleRegistry`
  - `NavigationService`
  - Spatie Permission (roles/permissions)
  - Shared layouts/components.
- With all modules disabled, the application (auth, dashboard, layouts) must still function.

## 13. Core System Isolation

**Core Principles:**
- Core system must never depend on modules
- Core routes, views, components work independently
- Core sidebar shows only core menu items if no modules enabled
- Core permissions system works without modules
- Module service providers should not modify core files
- Modules interact with core via interfaces/contracts

**Core Components:**
- Layout: `resources/views/layouts/app.blade.php` – never overridden by modules
- Sidebar: `resources/views/layouts/sidebar.blade.php` – uses MenuRegistry (empty if no modules)
- Components: `resources/views/components/` – core components always available
- Assets: `resources/css/app.css`, `resources/js/app.js` – core assets always loaded

**Ensuring Core Stability:**
- Core routes defined in `routes/web.php` (not in modules)
- Core views in `resources/views/` (not in modules)
- Core never calls module-specific code directly
- Core provides interfaces/registries for modules to register themselves
- When modules disabled, core continues to function normally
- Test core functionality with all modules disabled

**Module-Core Interface:**
- **MenuRegistry:** Modules register menu items; core builds sidebar from registry
- **PermissionRegistry:** Modules register permissions; core uses for role management
- **View Namespaces:** Modules register view namespaces; core resolves views
- **Route Registration:** Modules register routes; core includes them in routing
- **Asset Registration:** Modules register assets; core includes in build

**Testing Core Isolation:**
- Disable all modules and verify core still works
- Verify core routes accessible without modules
- Verify core sidebar shows (empty or core items only)
- Verify core permissions work without modules
- Verify core components render correctly

---

*End of AI Agent UI/UX Template Guide. Use this document as the single source of truth when generating or refactoring UI/UX in this project or in new applications that should match this template.*
