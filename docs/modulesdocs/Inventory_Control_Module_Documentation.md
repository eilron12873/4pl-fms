# Inventory Control Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Inventory Control** module (implemented as the `InventoryValuation` module) provides:

- A **single source of truth** for **company-owned stock** (not customer/custody stock).
- Real-time **quantity, cost, and value** of inventory by warehouse and item.
- A controlled mechanism to **record movements, adjustments, and write-offs**.
- **Warehouse and item master data** for financial reporting and costing.

Custody (customer-owned) inventory is managed in the external **WMS**; this module focuses only on **own inventory** and its financial value inside LFS.

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/InventoryValuation`
- **Layers**:
  - `Domain`: `InventoryValuation` domain root and conceptual valuation rules.
  - `Application`: `InventoryValuationService`, `InventoryValuationOverview`.
  - `Infrastructure`: Eloquent models (`Warehouse`, `InventoryItem`, `InventoryMovement`, `InventoryBalance`) and `InventoryValuationRepository`.
  - `UI`: `InventoryValuationController` and Blade views (dashboard, valuation, movements, adjustments, warehouses, items).
  - `API`: `api.php` placeholder for future integration endpoints.
- **Service provider**: `InventoryValuationServiceProvider` registers routes, views, and services for the module.

Database tables (via migrations):

- `warehouses` – inventory locations (code, name, notes, active flag).
- `inventory_items` – item master (code, name, SKU, unit, valuation method).
- `inventory_movements` – all stock movements (receipts, issues, transfers, adjustments, write-offs).
- `inventory_balances` – current quantity, cost, and value per warehouse and item.

---

## 3. Key Components

### 3.1 Models (Infrastructure)

- `Warehouse`
  - Fields: `code`, `name`, `is_active`, `notes`.
  - Used for:
    - Movement capture (warehouse selection).
    - Valuation reports (grouping by warehouse).

- `InventoryItem`
  - Fields: `code`, `name`, `sku`, `unit`, `valuation_method`, `is_active`.
  - `valuation_method` supports at least: `weighted_avg` and `fifo` (future).
  - Used in movements and valuation reporting.

- `InventoryMovement`
  - Represents a **single stock movement**:
    - Fields: `warehouse_id`, `item_id`, `movement_type`, `quantity`, `unit_cost`, `reference`, `movement_date`, `notes`.
    - Movement types: `receipt`, `issue`, `transfer_in`, `transfer_out`, `adjustment`, `write_off`.
  - Helper methods (e.g. `isInbound()`) classify movements as inbound/outbound for valuation updates.

- `InventoryBalance`
  - Stores the **current state** per warehouse & item:
    - Fields: `warehouse_id`, `item_id`, `quantity`, `unit_cost`, `value`, `last_movement_at`.
  - Drives valuation reports (`quantity`, `unit_cost`, `value`).

### 3.2 InventoryValuationService (Application Layer)

`InventoryValuationService` encapsulates valuation logic:

- `recordMovement(warehouse_id, item_id, movement_type, quantity, unit_cost, reference, movement_date, notes): InventoryMovement`
  - Creates a new `InventoryMovement`.
  - Calls `updateBalanceFromMovement()` to update `InventoryBalance`.

- `updateBalanceFromMovement(InventoryMovement $movement)`
  - Fetches/creates the corresponding `InventoryBalance`.
  - Maintains **weighted-average cost** (for inbound movements):
    - New unit cost \( = \frac{(old\_qty \times old\_cost) + (mov\_qty \times mov\_cost)}{old\_qty + mov\_qty} \).
  - Adjusts quantity up or down based on movement type.
  - Zeros cost if quantity goes to or below zero.
  - Sets `last_movement_at`.

- `valuationReport(?int $warehouseId, ?int $itemId): Collection`
  - Returns rows of:
    - Warehouse ID/code/name, item ID/code/name, quantity, unit cost, total value.
  - Filters to **active warehouses and items** and optional warehouse/item filters.

- `totalValuation(?int $warehouseId): float`
  - Sums `value` over the `valuationReport` result for a given warehouse or all warehouses.

### 3.3 Controller & Routes (UI Layer)

`InventoryValuationController` orchestrates user interactions:

- `index()` – Inventory Control dashboard with total own inventory value.
- `valuation()` – valuation report by warehouse and item.
- `movements()` / `movementCreate()` / `movementStore()` – stock movements list + create.
- `adjustments()` / `adjustmentCreate()` / `adjustmentStore()` – adjustments and write-offs list + create.
- `warehouses()` / `warehouseCreate()` / `warehouseStore()` – warehouse master management.
- `items()` / `itemCreate()` / `itemStore()` – item master management.

Routes (`app/Modules/InventoryValuation/routes.php`):

- Prefix: `inventory-valuation`
- Name: `inventory-valuation.*`
- Middleware: `auth`, `verified`, `permission:inventory-valuation.view`  
  (mutating actions further restricted by `inventory-valuation.manage`).

Key routes:

- Dashboard:
  - `GET /inventory-valuation` → `index()`.
- Valuation:
  - `GET /inventory-valuation/valuation` → `valuation()`.
- Stock Movements:
  - `GET /inventory-valuation/movements` → `movements()`.
  - `GET /inventory-valuation/movements/create` → `movementCreate()`.
  - `POST /inventory-valuation/movements` → `movementStore()`.
- Adjustments:
  - `GET /inventory-valuation/adjustments` → `adjustments()`.
  - `GET /inventory-valuation/adjustments/create` → `adjustmentCreate()`.
  - `POST /inventory-valuation/adjustments` → `adjustmentStore()`.
- Warehouses:
  - `GET /inventory-valuation/warehouses` → `warehouses()`.
  - `GET /inventory-valuation/warehouses/create` → `warehouseCreate()`.
  - `POST /inventory-valuation/warehouses` → `warehouseStore()`.
- Items:
  - `GET /inventory-valuation/items` → `items()`.
  - `GET /inventory-valuation/items/create` → `itemCreate()`.
  - `POST /inventory-valuation/items` → `itemStore()`.

---

## 4. Navigation Menus & Screens

### 4.1 Inventory Control Dashboard

Path: `Inventory Control → Home` (`/inventory-valuation`).

- Intro text:
  - Clarifies **company-owned stock only**; custody stock is managed in WMS.

Cards under “Own inventory (company stock)”:

- **Valuation Report**
  - Route: `/inventory-valuation/valuation`.
  - Provides current stock quantity, unit cost, and value by warehouse & item.
- **Stock Movements**
  - Route: `/inventory-valuation/movements`.
  - Shows movement history and allows drilling into receipts, issues, transfers.
- **Write-Off & Adjustments**
  - Route: `/inventory-valuation/adjustments`.
  - Focused list of adjustment and write-off movements.
- **Warehouses**
  - Route: `/inventory-valuation/warehouses`.
  - Warehouse master list and creation.

The dashboard also shows:

- **Total own inventory value** – aggregated from `InventoryBalance`.
- A short summary of how many balance lines exist across warehouses.

### 4.2 Valuation Report

- Route: `GET /inventory-valuation/valuation`.
- Filters:
  - `Warehouse` (dropdown) – shows only balances for the selected warehouse.
  - `Item` (dropdown) – shows only balances for the selected item.
- Output:
  - Table of:
    - Warehouse (code/name).
    - Item (code/name).
    - Quantity on hand.
    - Unit cost (weighted average).
    - Total value.
  - Summary of **total inventory value** for the filter scope.
- Use cases:
  - Month-end and ad-hoc inventory valuation.
  - Per-warehouse or per-item stock value analysis.

### 4.3 Stock Movements

- List page:
  - Route: `GET /inventory-valuation/movements`.
  - Filters:
    - `Warehouse` – filter movements by location.
    - `Item` – filter by item.
    - `Movement type` – filter by type (`receipt`, `issue`, `transfer_in`, etc.).
  - Table columns:
    - Movement date, warehouse, item, movement type, quantity, unit cost, reference.
  - Used for:
    - Auditing **movement history**.
    - Reconciling differences between WMS and FMS.

- Create movement:
  - Route: `GET /inventory-valuation/movements/create` (requires `inventory-valuation.manage`).
  - Fields:
    - Warehouse, item, movement type, quantity, unit cost (for inbound), reference, movement date, notes.
  - On submit:
    - `POST /inventory-valuation/movements` → `movementStore()` → `recordMovement()`.
    - Updates `InventoryBalance` accordingly.
  - Typical uses:
    - Manual receipts/issues when integrating with a non-real-time WMS.
    - Transfers between warehouses (`transfer_in`/`transfer_out` pairs).

### 4.4 Adjustments & Write-Offs

- List page:
  - Route: `GET /inventory-valuation/adjustments`.
  - Filters:
    - `Warehouse` – to focus on a particular location.
  - Shows movements with types: `adjustment`, `write_off`.
  - Columns:
    - Movement date, warehouse, item, quantity, unit cost, reason.

- Create adjustment:
  - Route: `GET /inventory-valuation/adjustments/create` (requires manage permission).
  - Fields:
    - Warehouse, item, **type** (`adjustment` or `write_off`), quantity, unit cost, reason, movement date.
  - Behavior:
    - For `write_off`, positive quantity is converted to a **negative movement** in the service layer.
    - Recorded via `recordMovement()` as a special movement type.
  - Use cases:
    - Cycle-count adjustments.
    - Damaged/lost stock write-offs with proper reason logging.

### 4.5 Warehouses Menu

- Warehouse list:
  - Route: `GET /inventory-valuation/warehouses`.
  - Columns: code, name, (optionally active status), notes.
  - Pagination for many warehouses.
- Add warehouse:
  - Route: `GET /inventory-valuation/warehouses/create`.
  - Fields: code, name, notes.
  - On submit:
    - `POST /inventory-valuation/warehouses` creates a new warehouse.
- Use cases:
  - Maintain a **controlled list of inventory locations** for use in movements and reports.

### 4.6 Items Menu

- Item list:
  - Route: `GET /inventory-valuation/items`.
  - Columns: code, name, SKU, unit, valuation method.
  - Pagination for large item catalogs.

- Add item:
  - Route: `GET /inventory-valuation/items/create`.
  - Fields:
    - Code, name, SKU, unit, valuation method (`weighted_avg` or `fifo`).
  - Defaults:
    - Unit = `EA`.
    - Valuation method = `weighted_avg`.
  - On submit:
    - `POST /inventory-valuation/items` creates a new item.
- Use cases:
  - Define **inventory items** that will be tracked for quantity and value.

---

## 5. End-to-End Workflows

### 5.1 Movement to Valuation Flow

1. User or integration records a **movement**:
   - Receipts, issues, transfers, adjustments, write-offs.
2. `InventoryValuationService::recordMovement()`:
   - Persists `InventoryMovement`.
   - Updates `InventoryBalance` for the affected warehouse & item.
3. Valuation reports:
   - Use `InventoryBalance` to compute quantity, unit cost, and value.
4. Other modules (e.g. CostingEngine) can:
   - Use the same `Warehouse` and `InventoryItem` masters for dimension alignment.

### 5.2 Period-End Inventory Control

1. Perform cycle counts and reconcile with WMS.
2. Record necessary **adjustments** and **write-offs**.
3. Review **Valuation Report** by warehouse/item.
4. Use **total inventory value** in financial statements (e.g. balance sheet).

---

## 6. Design Decisions & Guarantees

- **Weighted Average Cost**
  - Default valuation method maintains a **moving weighted-average cost** per warehouse & item.
  - Prevents cost swings from individual high/low price movements.

- **Per-Warehouse Balances**
  - Balances tracked per `(warehouse, item)` pair, aligning with multi-warehouse operations.

- **Audit-Friendly Movements**
  - All quantity changes must go through an `InventoryMovement`.
  - Adjustments and write-offs are explicit movement types with reasons and dates.

- **Company Stock Only**
  - The design intentionally **excludes customer inventory**, reducing complexity and duplication with WMS.

---

## 7. Recommended Enhancements

These are **optional improvements** for future inventory control evolution.

### 7.1 FIFO Valuation Implementation

- Fully implement `fifo` valuation method:
  - Maintain **layers** of receipts with remaining quantities and costs.
  - Issues and write-offs consume from the oldest layers.
  - Valuation reports should be aware of method per item.

### 7.2 Tighter WMS Integration

- Add:
  - **Reconciliation reports** comparing WMS stock vs. FMS balances.
  - Automated import of WMS movements for company-owned inventory.
  - Alerts when differences exceed defined thresholds.

### 7.3 Inventory Aging & Turnover

- Extend reporting to:
  - Age inventory (by receipt date) to find slow-moving or obsolete stock.
  - Compute **inventory turnover**, days on hand, and other KPIs.

### 7.4 GL Integration Hooks

- Integrate movements with Core Accounting (see `Core_Accounting_Module_Documentation.md` for the journal engine):
  - Optional posting of **inventory and COGS journals** (e.g. on issue/write-off) using `JournalService`.
  - This would turn movements into fully auditable financial events.

### 7.5 Reservations & Safety Stock

- Add attributes for:
  - **Reserved quantity** (for orders).
  - **Safety stock levels** per warehouse & item.
- Reports to show:
  - Available-to-promise, below-safety-stock alerts.

### 7.6 Batch/Lot & Serial Support

- For certain deployments:
  - Support batch/lot or serial numbers in movements and balances.
  - Add UI filters and valuation summaries by batch/lot.

---

## 8. Summary

The Inventory Control module in LFS (InventoryValuation) provides a focused, finance-friendly view of **company-owned inventory**:

- Maintains per-warehouse, per-item balances with weighted-average cost.
- Offers clear movement, adjustment, and write-off workflows.
- Supports valuation reporting used for financial statements and costing.

The recommended enhancements above focus on valuation sophistication (FIFO), stronger WMS/GL integration, and richer inventory analytics for decision support and risk reduction.

