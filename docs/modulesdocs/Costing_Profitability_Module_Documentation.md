# Costing & Profitability Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Costing & Profitability (CostingEngine)** module provides **multi-dimensional profitability analysis** for LFS.  
It answers questions such as:

- Which **clients** are most/least profitable?
- What is the **profit per shipment**, route, warehouse, or project?
- How do **revenue and cost** trends behave over time across operational dimensions?

This module consumes:

- **Revenue** primarily from **issued AR invoices**.
- **Costs** from **posted journal lines** in Core Accounting tagged with **profitability dimensions** (client, shipment, route, warehouse, project).

It offers:

- **Out-of-the-box profitability reports** by dimension (client, shipment, route, warehouse, project).
- A placeholder **Allocation Engine** UI for future shared-cost allocations.

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/CostingEngine`
- **Layers**:
  - `Domain`: `CostingEngine` domain root and conceptual costing rules.
  - `Application`: `ProfitabilityService`, `CostingEngineOverview`, coordination of repository queries.
  - `Infrastructure`: `CostingEngineRepository`, `CostingEngineModel` (base model).
  - `UI`: `CostingEngineController` and Blade views for each profitability report and allocation engine.
  - `API`: `api.php` placeholder for future CostingEngine-specific APIs.
- **Service provider**: `CostingEngineServiceProvider` registers module routes, views, and services.

Data sources:

- **Core Accounting**
  - Uses `JournalLine` model, leveraging dimensions:
    - `client_id`, `shipment_id`, `route_id`, `warehouse_id`, `project_id`.
- **Billing Engine**
  - Uses `BillingClient` for client metadata (code, name, currency).
- **Inventory / Warehouse**
  - Uses `Warehouse` model from InventoryValuation for warehouse names/codes.

---

## 3. Key Components

### 3.1 ProfitabilityService (Application Layer)

`ProfitabilityService` encapsulates all profitability calculations, using `CostingEngineRepository` and account-code prefixes.

- **Revenue and expense prefixes**
  - `revenueAccountPrefixes()` → `['41', '42', '43', '44', '45', '46']`
  - `expenseAccountPrefixes()` → `['51', '52', '53', '54', '55', '56', '57']`
  - These define which GL accounts count as revenue vs. cost.

- **Client profitability**
  - `clientProfitability(?string $fromDate = null, ?string $toDate = null): Collection`
  - Revenue from **AR invoices** (issued/paid) via repository.
  - Cost from **journal lines** filtered by expense prefixes and `client_id`.
  - Joins with `BillingClient` to show client code/name.
  - Computes per-client:
    - Revenue, cost, margin, `margin_pct` (margin / revenue).

- **Warehouse profitability**
  - `warehouseProfitability(?string $fromDate = null, ?string $toDate = null): Collection`
  - Uses `revenueAndCostByDimension('warehouse_id', ...)` from repository.
  - Joins with `Warehouse` master to show code/name.

- **Shipment profitability**
  - `shipmentProfitability(?string $fromDate = null, ?string $toDate = null): Collection`
  - Uses `revenueAndCostByDimension('shipment_id', ...)`.
  - Focuses purely on dimension values (no shipment master in this module).

- **Route profitability**
  - `routeProfitability(?string $fromDate = null, ?string $toDate = null): Collection`
  - Uses `revenueAndCostByDimension('route_id', ...)`.

- **Project profitability**
  - `projectProfitability(?string $fromDate = null, ?string $toDate = null): Collection`
  - Uses `revenueAndCostByDimension('project_id', ...)`.

For each dimension, the service:

- Aggregates revenue and cost.
- Computes margin and margin percentage.
- Sorts the results by **margin** descending.

### 3.2 CostingEngineRepository (Infrastructure Layer)

`CostingEngineRepository` (simplified description based on usage):

- Provides methods used by `ProfitabilityService`:
  - `revenueByClientFromInvoices($from, $to)` – revenue aggregation from AR invoices.
  - `costByClientFromJournalLines($from, $to)` – cost aggregation from journal lines by client.
  - `revenueAndCostByDimension($dimension, $from, $to)` – generic aggregation for shipment, route, warehouse, project.
- Uses:
  - `JournalLine` model for cost (and sometimes revenue) side.
  - Account code prefixes to filter revenue vs. expense.

### 3.3 Controller & Routes (UI Layer)

`CostingEngineController` exposes report screens:

- `index()` → Costing & Profitability dashboard.
- `clientProfitability()` → client-level profitability.
- `shipmentProfitability()` → shipment-level profitability.
- `routeProfitability()` → route-level profitability.
- `warehouseProfitability()` → warehouse-level profitability.
- `projectProfitability()` → project-level profitability.
- `allocationEngine()` → placeholder screen describing future allocation rules.

Routes (`app/Modules/CostingEngine/routes.php`):

- Prefix: `costing-engine`
- Name: `costing-engine.*`
- Middleware: `auth`, `verified`, `permission:costing-engine.view`

---

## 4. Navigation Menus & Screens

### 4.1 Costing & Profitability Dashboard

Path: `Costing & Profitability → Home` (`/costing-engine`).

- Intro text:
  - Explains that revenue comes from AR invoices and cost from GL journal lines with dimensions.

Cards:

- **Client Profitability**
  - Route: `/costing-engine/client-profitability`.
  - Revenue vs. cost by client.
- **Shipment Profitability**
  - Route: `/costing-engine/shipment-profitability`.
  - Revenue and cost by shipment ID.
- **Route Profitability**
  - Route: `/costing-engine/route-profitability`.
  - Revenue and cost by route.
- **Warehouse Profitability**
  - Route: `/costing-engine/warehouse-profitability`.
  - Revenue and cost by warehouse.
- **Project Profitability**
  - Route: `/costing-engine/project-profitability`.
  - Revenue and cost by project.
- **Allocation Engine**
  - Route: `/costing-engine/allocation-engine`.
  - Placeholder for defining allocation rules for shared costs.

Each report screen has a **“Back to Costing”** link returning to this dashboard.

### 4.2 Client Profitability Screen

- Route: `GET /costing-engine/client-profitability`.
- Filter form:
  - `From date`, `To date` – optional date range; if omitted, uses all data.
  - **Apply** button recomputes results for the selected period.
- Table columns:
  - Client (code + name).
  - Revenue.
  - Cost.
  - Margin (revenue − cost).
  - Margin % (margin / revenue).
- Notes:
  - Revenue is sourced from **issued AR invoices**.
  - Cost is sourced from **journal lines** tagged with `client_id` and expense prefixes.
- Use cases:
  - Ranking clients by profitability.
  - Comparing margins across clients for pricing and contract reviews.

### 4.3 Shipment Profitability Screen

- Route: `GET /costing-engine/shipment-profitability`.
- Filter form:
  - `From date`, `To date` – optional.
- Table columns:
  - Shipment ID.
  - Revenue, Cost, Margin, Margin %.
- Notes:
  - Uses journal lines dimension `shipment_id`.
  - Designed to integrate with WMS/LMS shipment identifiers.
- Use cases:
  - Analyzing profitability on a **per-shipment** basis.
  - Identifying loss-making shipments.

### 4.4 Route Profitability Screen

- Route: `GET /costing-engine/route-profitability`.
- Filter form:
  - `From date`, `To date`.
- Table columns:
  - Route ID.
  - Revenue, Cost, Margin, Margin %.
- Notes:
  - Uses `route_id` dimension from journal lines.
- Use cases:
  - Determining profitable vs. unprofitable **routes**.
  - Supporting routing and pricing decisions.

### 4.5 Warehouse Profitability Screen

- Route: `GET /costing-engine/warehouse-profitability`.
- Filter form:
  - `From date`, `To date`.
- Table columns:
  - Warehouse (code + name).
  - Revenue, Cost, Margin, Margin %.
- Notes:
  - Uses `warehouse_id` dimension.
  - Joins with `Warehouse` master for human-readable names/codes.
- Use cases:
  - Evaluating **warehouse performance** and utilization profitability.
  - Supporting decisions about warehouse expansions or consolidation.

### 4.6 Project Profitability Screen

- Route: `GET /costing-engine/project-profitability`.
- Filter form:
  - `From date`, `To date`.
- Table columns:
  - Project ID.
  - Revenue, Cost, Margin, Margin %.
- Notes:
  - Uses `project_id` dimension.
- Use cases:
  - Tracking profitability of **logistics projects**.
  - Supporting project-level P&L reporting.

### 4.7 Allocation Engine Screen (Planned Feature)

- Route: `GET /costing-engine/allocation-engine`.
- Current behavior:
  - Informational page describing the planned **Allocation Engine**.
  - Explains that future versions will allow:
    - Defining rules for allocating shared costs (overhead, depreciation, warehouse space).
    - Allocation targets: clients, shipments, routes, warehouses, projects.
  - Advises users to ensure journal entries already carry appropriate dimensions.
- Planned use:
  - Central place to manage allocation rules that will feed into profitability calculations.

---

## 5. End-to-End Workflows

### 5.1 Data Flow from GL and AR to Costing

1. **Operational events** occur (shipments, storage, transport, project work).
2. **Core Accounting** posts journals with:
   - Revenue to accounts with prefixes 41–46.
   - Expense to accounts with prefixes 51–57.
   - Profitability dimensions set where applicable (`client_id`, `shipment_id`, `route_id`, `warehouse_id`, `project_id`).
3. **AR module** issues invoices, which:
   - Provide revenue by client (and sometimes by other dimensions via invoice lines).
4. **CostingEngineRepository** aggregates:
   - Revenue by client from AR invoices.
   - Revenue and cost by dimensions from journal lines.
5. **ProfitabilityService**:
   - Merges revenue and cost per dimension.
   - Computes margin and margin percentage.
6. **UI screens**:
   - Display the aggregated results via the profitability views described above.

### 5.2 Using Profitability Reports in Daily Operations

- Operations/finance teams:
  - Regularly run **client profitability** to monitor key customers.
  - Use **route** and **warehouse** profitability to optimize network and facility decisions.
  - Use **shipment** and **project** profitability to investigate outliers and exceptions.

---

## 6. Design Decisions & Guarantees

- **Dimension-Based Design**
  - Costing relies entirely on **dimensions on journal lines** and, for clients, AR invoices.
  - There is no separate costing ledger; instead, it reuses GL and AR data.

- **Configurable via Account Code Prefixes**
  - Revenue vs. cost classification is based on account code prefixes.
  - This keeps the module decoupled from specific chart-of-accounts structures.

- **Read-Only Analytics**
  - CostingEngine is **read-only** with respect to financial data; it does not post any new journals.
  - It only aggregates and displays metrics, preserving core accounting integrity.

---

## 7. Recommended Enhancements

These are **optional improvements** to extend the power and flexibility of the Costing & Profitability module.

### 7.1 Configurable Dimension & Account Mappings

- Move revenue/expense prefixes and dimension usage into configuration:
  - Allow per-tenant overrides (e.g. which GL ranges count as revenue/cost).
  - Enable toggling additional dimensions (e.g. vehicle, service line, cost center).

### 7.2 Drill-Down and Detail Links

- Add drill-down from summary rows to:
  - Underlying **journals** and **journal lines**.
  - **Invoices** and operational objects (shipments, routes, projects) via deep links to WMS/LMS.
- This would aid root-cause analysis for outlier profitability.

### 7.3 Saved Views & Filters

- Allow users to:
  - Save commonly used date ranges and filter presets (e.g. “Last quarter”, “This year to date”).
  - Export profitability reports to CSV/Excel for further analysis.

### 7.4 Allocation Engine Implementation

- Implement the planned **Allocation Engine**:
  - Rule types:
    - Revenue proportion allocation.
    - Volume-based allocation (e.g. pallets, CBM, trips).
    - Fixed or percentage-based allocations.
  - Apply allocations at posting time or as a periodic batch (e.g. month-end).
  - Ensure allocated costs are visible as separate lines/dimensions in GL for audit.

### 7.5 Multi-Currency and FX Handling

- For multi-currency environments:
  - Normalize revenue and cost to a **functional currency** for margin analysis.
  - Optionally expose **FX impact** (e.g. separate FX gain/loss lines).

### 7.6 Performance & Caching

- For large datasets:
  - Introduce **materialized views** or summary tables for profitability snapshots.
  - Add caching per date range and dimension to speed up dashboard loading.

---

## 8. Summary

The Costing & Profitability (CostingEngine) module provides a **dimension-rich profitability layer** on top of LFS’s Core Accounting and AR data, delivering:

- Profitability by client, shipment, route, warehouse, and project.
- Simple, date-filterable reports aligned with GL and AR.
- A clear path for future allocation logic via the Allocation Engine.

The enhancement ideas above focus on making profitability more configurable, drillable, and scalable as transaction volume and analytical needs grow.

