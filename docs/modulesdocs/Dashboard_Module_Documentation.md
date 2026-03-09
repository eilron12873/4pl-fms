# Dashboard Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Dashboard** module provides the **entry point** and **top-level executive views** for LFS.  
It appears as the first item in the main navigation and serves to:

- Redirect authenticated users to a **central “Dashboard” page** after login.
- Expose navigation entries for:
  - **Executive Dashboard** – high-level financial KPIs (planned).
  - **Operations Financial Snapshot** – operational-financial KPIs (planned).

In the current implementation:

- The main `/dashboard` route renders a **simple stub page** (`resources/views/dashboard.blade.php`) showing “You’re logged in!”.
- Detailed KPI dashboards are **documented in the UI Navigation Blueprint**, and some specialized dashboards (e.g. AR/AP KPI dashboard) are implemented under other modules (e.g. Financial Reporting), not in a dedicated Dashboard module yet.

This document explains:

- How the Dashboard entry was created and wired (routes, navigation, layout).
- Tech stack and architecture.
- Current behavior and workflows.
- How the Dashboard navigation items operate.
- Recommended enhancements to evolve this area into a full executive dashboard suite.

---

## 2. Tech Stack & Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Routing**: `routes/web.php`
  - `GET /dashboard` → returns the `dashboard` Blade view.
  - Middleware: `auth`, `verified`.
  - Route name: `dashboard.index`.
- **View**: `resources/views/dashboard.blade.php`
  - Uses the shared `x-app-layout` layout component.
  - Displays a simple header “Dashboard” and a body message “You’re logged in!”.
- **Navigation configuration**: `config/navigation.php`
  - Top-level group:
    - `label`: `Dashboard`
    - `icon`: `fas fa-tachometer-alt`
    - `order`: `10`
  - Child items (design-time only, both currently route to the same page):
    - **Executive Dashboard**
      - `route`: `dashboard.index`
      - `icon`: `fas fa-chart-line`
      - `nav_key`: `dashboard_executive`
    - **Operations Financial Snapshot**
      - `route`: `dashboard.index`
      - `icon`: `fas fa-project-diagram`
      - `nav_key`: `dashboard_operations`
- **Navigation layout**: `resources/views/layouts/navigation.blade.php`
  - Contains a top-left brand area and a responsive nav link pointing to `route('dashboard.index')` labeled “Dashboard”.

There is **no dedicated Laravel module folder** (`app/Modules/Dashboard`) yet; the Dashboard is implemented as part of the core app routing and shared layout.

---

## 3. Current Implementation Details

### 3.1 Route & Auth Integration

- The main route is defined in `routes/web.php`:
  - `Route::get('/dashboard', function () { return view('dashboard'); })`
  - Wrapped in:
    - `middleware(['auth', 'verified'])`
    - Named: `dashboard.index`
- Authentication and verification controllers (from Breeze) are aligned to this route:
  - After login, registration, and email verification, they redirect to `route('dashboard.index', absolute: false)`.
  - `AppServiceProvider` also uses `dashboard.index` as the canonical “home” route when available.

### 3.2 Dashboard View (`resources/views/dashboard.blade.php`)

- Structure:
  - `x-app-layout` wrapper with a `header` slot showing “Dashboard”.
  - Content:
    - A single card with the text “You’re logged in!”.
- Purpose:
  - Acts as a **placeholder home page**.
  - Confirms successful authentication and email verification.
  - Provides a simple, safe landing point while specialized dashboards are implemented in domain modules (AR/AP, Treasury, Financial Reporting, etc.).

---

## 4. Navigation Menus & Screens

### 4.1 Sidebar “Dashboard” Group

- Defined in `config/navigation.php` as the first top-level group:
  - Label: `Dashboard`
  - Icon: `fas fa-tachometer-alt`
  - Order: `10`
- Children:
  - **Executive Dashboard**
    - Route: `dashboard.index`
    - Icon: `fas fa-chart-line`
    - Intended behavior:
      - Show high-level financial KPIs (revenue, margin, AR/AP summary, cash position).
      - Currently still a concept in `docs/LFS_UI_Navigation_Blueprint.md` (“1.1 Executive Dashboard”).
  - **Operations Financial Snapshot**
    - Route: `dashboard.index`
    - Icon: `fas fa-project-diagram`
    - Intended behavior:
      - Show operational-financial metrics (accrued revenue, accrued cost, shipment profitability).
      - Currently routes to the same basic `/dashboard` page.

Both children currently land on the **same stub view**, but navigation keys distinguish them for future per-dashboard implementations and role-based visibility.

### 4.2 Top Navigation / App Header

- In `resources/views/layouts/navigation.blade.php`:
  - The main nav includes a link to `route('dashboard.index')` labeled “Dashboard”.
  - The mobile/responsive nav also includes a “Dashboard” link using `x-responsive-nav-link`.
- Behavior:
  - Clicking anywhere labeled “Dashboard” sends the user to `/dashboard`.
  - Active route highlighting (`request()->routeIs('dashboard.index')`) ensures the Dashboard tab is marked active when on the home page.

---

## 5. Relation to Other Dashboards

LFS uses **module-specific dashboards** implemented within their respective modules, for example:

- **Core Accounting** – `Core Accounting → Home` (`/core-accounting`), see `Core_Accounting_Module_Documentation.md`.
- **Accounts Receivable** – AR Home Dashboard (`/accounts-receivable`), see `Accounts_Receivable_Module_Documentation.md`.
- **Accounts Payable** – AP Home Dashboard (`/accounts-payable`), see `Accounts_Payable_Module_Documentation.md`.
- **Costing & Profitability** – `/costing-engine`, see `Costing_Profitability_Module_Documentation.md`.
- **Inventory Control** – `/inventory-valuation`, see `Inventory_Control_Module_Documentation.md`.
- **Fixed Assets** – `/fixed-assets`, see `Fixed_Assets_Module_Documentation.md`.
- **Treasury & Cash** – `/treasury`, see `Treasury_Cash_Module_Documentation.md`.
- **Financial Reporting** – `/financial-reporting` + AR/AP KPI Dashboard, see `Financial_Reporting_Module_Documentation.md`.

The **Dashboard** module (as defined here) is:

- A **global landing page** and menu group.
- A conceptual parent for:
  - Executive Dashboard.
  - Operations Financial Snapshot.
- Complemented by domain-specific dashboards that live in each module.

---

## 6. Workflows & Usage Patterns

### 6.1 User Login & Landing

1. User navigates to the LFS login page and signs in.
2. Authentication controllers redirect to `route('dashboard.index')`.
3. Middleware enforces:
   - User is authenticated.
   - Email is verified (via `verified` middleware).
4. The `dashboard` view is rendered:
   - Header: “Dashboard”.
   - Message: “You’re logged in!”.

From here, the user typically:

- Uses the sidebar to navigate into domain modules (AR/AP, Treasury, etc.).
- For future versions, would use:
  - Executive Dashboard for a **CFO-level overview**.
  - Operations Snapshot for **operations/finance joint monitoring**.

### 6.2 Returning Users

- When a logged-in user clicks:
  - The logo/brand link that points to `dashboard.index`, or
  - The “Dashboard” item in the sidebar or header,
- They are taken back to `/dashboard`, which:
  - Confirms they are logged in.
  - Serves as a neutral starting point between modules.

---

## 7. Design Decisions & Guarantees

- **Minimal, safe default**:
  - The current Dashboard view is intentionally simple to avoid heavy queries or complex logic on login.
  - All complex KPIs and domain-specific visuals are deferred to each module’s home dashboard and to Financial Reporting / KPI dashboards.
- **Navigation consistency**:
  - `dashboard.index` is used consistently across:
    - Navigation (`config/navigation.php`).
    - Auth controllers.
    - `AppServiceProvider` (home redirect).
- **Blueprint alignment**:
  - The **UI Navigation Blueprint** (`docs/LFS_UI_Navigation_Blueprint.md`) defines:
    - “1.1 Executive Dashboard”.
    - “1.2 Operations Financial Snapshot”.
  - Current implementation keeps these as menu entries pointing to the same route, preparing for future separation without breaking navigation.

---

## 8. Recommended Enhancements

### 8.1 Implement Executive Dashboard View

- Create a dedicated controller + view, e.g.:
  - `DashboardController@executive()`.
  - Route: `GET /dashboard/executive` → named `dashboard.executive`.
- KPIs to include:
  - Revenue and margin (current month vs prior).
  - AR balance, AP balance, net working capital.
  - Cash position from Treasury (sum of active bank accounts).
  - High-level DSO and DPO from AR/AP reporting.
- Wire `config/navigation.php`:
  - `Executive Dashboard` → `route: dashboard.executive`.

### 8.2 Implement Operations Financial Snapshot View

- New controller method + view, e.g.:
  - `DashboardController@operations()`.
  - Route: `GET /dashboard/operations` → `dashboard.operations`.
- Metrics:
  - Accrued revenue vs invoiced revenue (from Core Accounting + AR).
  - Accrued cost vs vendor bills (Core Accounting + AP + Procurement).
  - Shipment-level profitability snapshots (from Costing & Profitability).
  - Open P.R./P.O. by status (from Procurement).
- Wire navigation:
  - `Operations Financial Snapshot` → `dashboard.operations`.

### 8.3 Role-Based Dashboard Variants

- Use existing RBAC (`spatie/laravel-permission`) to:
  - Show different **default dashboards** per role (CFO vs Operations Manager vs AR/AP Officer).
  - Optionally:
    - Redirect different roles to different dashboards after login.
    - Or provide quick links/cards to their most relevant module dashboards.

### 8.4 Widgetized Dashboard Layout

- Introduce a **widget system**:
  - Each module (AR, AP, Treasury, etc.) can provide small widgets/cards:
    - Today’s collections, overdue AR, upcoming AP payments, cash position, etc.
  - Dashboard aggregates these into:
    - Executive view.
    - Operations view.
- Benefits:
  - Modular and extensible design.
  - Easy to add/remove widgets per client or deployment.

### 8.5 Performance & Caching

- As KPI dashboards are implemented:
  - Use cached aggregates and background jobs for heavy computations.
  - Ensure the `/dashboard` load is **fast and predictable**, even with large datasets.
- Consider:
  - Daily or hourly pre-computation for expensive metrics.
  - On-demand drill-down links to detailed reports rather than loading everything on the home page.

### 8.6 Deep Links to Module Dashboards

- Enhance the central Dashboard page to:
  - Show cards linking to each domain home dashboard:
    - Core Accounting, AR, AP, Treasury, Fixed Assets, Inventory, Costing, Procurement, Financial Reporting.
  - Provide a quick “hub” with at-a-glance counts and shortcuts.

---

## 9. Summary

The **Dashboard** module today acts as a simple, secure landing page and a navigation group for future executive and operations dashboards.  
As you implement dedicated Executive and Operations dashboards, widgetized KPIs, role-based variants, and performance-conscious aggregation, this area can evolve into the primary **command center** for CFOs, finance managers, and operations leaders using LFS.  
The existing module-specific dashboards remain the natural place for deep, domain-specific analysis, with the central Dashboard stitching them together into a cohesive story. 

