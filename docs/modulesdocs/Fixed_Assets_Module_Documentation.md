# Fixed Assets Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Fixed Assets** module manages the **asset register, depreciation, and maintenance costs** for long-lived assets such as vehicles, equipment, IT, and buildings.

Its objectives are to:

- Maintain a **complete asset registry** with acquisition and lifecycle details.
- Automate **straight-line depreciation posting** into the General Ledger via Core Accounting (see `Core_Accounting_Module_Documentation.md`).
- Track **maintenance costs** per asset for total cost-of-ownership analysis.
- Provide **summary and detailed reports** on cost, accumulated depreciation, book value, and maintenance.

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/FixedAssets`
- **Layers**:
  - `Domain`: `FixedAssets` domain root and conceptual lifecycle rules.
  - `Application`: `FixedAssetService`, `FixedAssetsOverview`.
  - `Infrastructure`: Eloquent models (`FixedAsset`, `AssetMaintenance`), `FixedAssetsRepository`, base `FixedAssetsModel`.
  - `UI`: `FixedAssetsController` and Blade views (dashboard, asset registry, depreciation, maintenance, reports).
  - `API`: `api.php` placeholder for future endpoints.
- **Service provider**: `FixedAssetsServiceProvider` registers the module’s routes, views, and bindings.

Database tables:

- `fixed_assets` – asset master and lifecycle data.
- `asset_maintenance` – maintenance records and costs per asset.

Integration:

-- **Core Accounting / GL** (see `Core_Accounting_Module_Documentation.md`):
  - Uses `JournalService` and `PostingSource` to post depreciation journals (Depreciation expense / Accumulated depreciation) with idempotency.

---

## 3. Key Components

### 3.1 Models (Infrastructure)

- `FixedAsset`
  - Fields:
    - `code`, `name`, `asset_type` (`vehicle`, `equipment`, `it`, `building`, `other`).
    - `purchase_date`, `acquisition_cost`, `useful_life_years`, `residual_value`.
    - `depreciation_method` (currently `straight_line`).
    - GL mapping:
      - `gl_asset_code` (e.g. 1300).
      - `gl_accum_depn_code` (e.g. 1320).
      - `gl_depn_expense_code` (e.g. 5400).
    - Status & lifecycle:
      - `status` (`active`, `disposed`), `last_depreciation_at`, `disposed_at`.
    - Operational metadata:
      - `location`, `custodian`, `notes`.
    - Accumulation:
      - `accumulated_depreciation`.
  - Casts: dates and decimals for monetary amounts.
  - Relations:
    - `maintenanceRecords()` → `AssetMaintenance` (`hasMany`).
  - Helpers:
    - `isActive()` – status check.
    - `depreciableAmount()` – acquisition cost − residual value.
    - `bookValue()` – acquisition cost − accumulated depreciation.

- `AssetMaintenance`
  - Fields:
    - `fixed_asset_id`, `maintenance_date`, `amount`, `description`, `reference`.
  - Casts: `maintenance_date` (date), `amount` (decimal).
  - Relation:
    - `fixedAsset()` → `FixedAsset`.

### 3.2 FixedAssetService (Application Layer)

`FixedAssetService` encapsulates business logic for registration, disposal, depreciation, and maintenance:

- **Register asset**
  - `register(array $data): FixedAsset`
    - Creates a new asset with GL account codes and default `straight_line` depreciation method.
    - Initializes status as `active`.

- **Dispose asset**
  - `dispose(FixedAsset $asset, ?string $disposedAt = null): void`
    - Sets status to `disposed` and records disposal date.
    - Future enhancements can add disposal journals (gain/loss on disposal).

- **Depreciation calculation**
  - `calculateDepreciationForPeriod(FixedAsset $asset, string $fromDate, string $toDate): float`
    - Straight-line monthly depreciation for the period.
    - Considers:
      - Useful life (years × 12 months).
      - Remaining depreciable amount.
      - In-service months within the period.

- **Run depreciation**
  - `runDepreciation(string $periodEndDate): array`
    - For each active asset:
      - Computes depreciation amount for the period.
      - Uses **idempotency** via `PostingSource`:
        - Skips posting if a `PostingSource` with the same `idempotency_key` exists.
      - Posts a journal (if amount > 0):
        - DR `gl_depn_expense_code`.
        - CR `gl_accum_depn_code`.
      - Increments `accumulated_depreciation` and updates `last_depreciation_at`.
    - Returns an array of results per asset: `asset_id`, `asset_code`, `amount`, `journal_id`.

- **Record maintenance**
  - `recordMaintenance(int $assetId, string $maintenanceDate, float $amount, ?string $description, ?string $reference): AssetMaintenance`
    - Creates a maintenance record for an asset.
    - Used for tracking maintenance cost in reports.

### 3.3 Controller & Routes (UI Layer)

`FixedAssetsController` orchestrates views and actions:

- Dashboard:
  - `index()` – Fixed Assets home; shows summary of active assets and aggregates.
- Asset registry:
  - `assets()` – list assets with filters for status and type.
  - `assetCreate()` / `assetStore()` – register new assets.
  - `assetShow()` – asset detail including maintenance records.
- Depreciation:
  - `depreciation()` – main depreciation screen (run + navigation to schedule/history).
  - `depreciationRun()` – runs depreciation for a selected period end date.
  - `depreciationSchedule()` – per-asset schedule (monthly/annual depreciation, remaining life).
  - `depreciationHistory()` – list of posted depreciation journals via `PostingSource`.
- Maintenance:
  - `maintenance()` – list maintenance records with optional asset filter.
  - `maintenanceCreate()` / `maintenanceStore()` – create new maintenance entries.
- Reports:
  - `reports()` – cost and profitability overview for assets (acquisition cost, accumulated depreciation, book value, maintenance totals, total cost).

Routes (`app/Modules/FixedAssets/routes.php`):

- Prefix: `fixed-assets`
- Name: `fixed-assets.*`
- Middleware: `auth`, `verified`, `permission:fixed-assets.view`  
  (mutating operations require `fixed-assets.manage`).

Key routes:

- Dashboard:
  - `GET /fixed-assets` → `index()`.
- Assets:
  - `GET /fixed-assets/assets` → `assets()`.
  - `GET /fixed-assets/assets/create` → `assetCreate()`.
  - `POST /fixed-assets/assets` → `assetStore()`.
  - `GET /fixed-assets/assets/{id}` → `assetShow()`.
- Depreciation:
  - `GET /fixed-assets/depreciation` → `depreciation()`.
  - `GET /fixed-assets/depreciation/schedule` → `depreciationSchedule()`.
  - `GET /fixed-assets/depreciation/history` → `depreciationHistory()`.
  - `POST /fixed-assets/depreciation/run` → `depreciationRun()`.
- Reports:
  - `GET /fixed-assets/reports` → `reports()`.
- Maintenance:
  - `GET /fixed-assets/maintenance` → `maintenance()`.
  - `GET /fixed-assets/maintenance/create` → `maintenanceCreate()`.
  - `POST /fixed-assets/maintenance` → `maintenanceStore()`.

---

## 4. Navigation Menus & Screens

### 4.1 Fixed Assets Dashboard

Path: `Fixed Assets → Home` (`/fixed-assets`).

Cards:

- **Asset Registry**
  - Route: `/fixed-assets/assets`.
  - Master list and register of fixed assets.
- **Depreciation**
  - Route: `/fixed-assets/depreciation`.
  - Entry point for depreciation schedule, history, and running depreciation.
- **Maintenance Cost Tracking**
  - Route: `/fixed-assets/maintenance`.
  - List and entry of maintenance records per asset.
- **Reports**
  - Route: `/fixed-assets/reports`.
  - Aggregated cost and profitability-style metrics for assets.

Summary section:

- **Count** of active assets.
- **Total cost** of active assets.
- **Accumulated depreciation** for active assets.
- **Net book value** (cost − accumulated depreciation).

### 4.2 Asset Registry

- List page:
  - Route: `GET /fixed-assets/assets`.
  - Filters:
    - `Status` – `active`, `disposed`, or all.
    - `Type` – `vehicle`, `equipment`, `it`, `building`, `other`.
  - Table columns:
    - Code, name, type, purchase date, acquisition cost, accumulated depreciation, book value, status, and `View` action.
  - Actions:
    - **Back** to dashboard.
    - **Register asset** (if user has `fixed-assets.manage`) → `/fixed-assets/assets/create`.

- Asset registration:
  - Route: `GET /fixed-assets/assets/create`.
  - Fields:
    - Code, name, asset type, purchase date, acquisition cost.
    - Useful life (years), residual value.
    - Optional: location, custodian, notes (GL codes are defaulted if not specified).
  - On submit:
    - `POST /fixed-assets/assets` → `assetStore()` → `FixedAssetService::register()`.

- Asset detail:
  - Route: `GET /fixed-assets/assets/{id}`.
  - Displays:
    - Asset header (master data, GL mapping, status).
    - Depreciation information (cost, accumulated depreciation, book value).
    - Maintenance records list for that asset.

### 4.3 Depreciation Menu

- Main screen:
  - Route: `GET /fixed-assets/depreciation`.
  - Actions:
    - **Depreciation schedule** link → `/fixed-assets/depreciation/schedule`.
    - **Depreciation history** link → `/fixed-assets/depreciation/history`.
  - Depreciation run:
    - For users with `fixed-assets.manage`:
      - Form with `Period end date` (typically last day of the month).
      - `Run depreciation` posts monthly depreciation for all active assets.
      - Uses idempotency to avoid double-posting for the same period.

- Depreciation schedule:
  - Route: `GET /fixed-assets/depreciation/schedule`.
  - Shows per asset:
    - Monthly and annual depreciation.
    - Elapsed months and remaining months in useful life.
  - Helpful for:
    - Planning and forecasting depreciation.

- Depreciation history:
  - Route: `GET /fixed-assets/depreciation/history`.
  - Data source:
    - `PostingSource` records with `source_system = 'fixed-assets'` and `event_type = 'depreciation'`.
  - Shows:
    - Past depreciation journals and links back to GL.
    - The assets each journal relates to (via `source_reference`).

### 4.4 Maintenance Cost Tracking

- List page:
  - Route: `GET /fixed-assets/maintenance`.
  - Filters:
    - `Asset` (active assets dropdown) to narrow the maintenance list.
  - Table columns:
    - Maintenance date, asset code/name, amount, description, reference.
  - Used to:
    - Track maintenance spend per asset.
    - Support total cost-of-ownership calculations.

- Create maintenance:
  - Route: `GET /fixed-assets/maintenance/create` (requires manage permission).
  - Fields:
    - Asset, maintenance date, amount, description, reference.
  - On submit:
    - `POST /fixed-assets/maintenance` → `maintenanceStore()` → `FixedAssetService::recordMaintenance()`.

### 4.5 Reports Menu

- Reports page:
  - Route: `GET /fixed-assets/reports`.
  - Uses `FixedAsset::withSum('maintenanceRecords', 'amount')`.
  - For each asset, the view computes:
    - Acquisition cost.
    - Accumulated depreciation.
    - Book value.
    - Total maintenance spend.
    - Total cost (book value + maintenance).
  - Use cases:
    - Asset profitability and utilization insights (especially for vehicles/equipment).
    - Determining replacement vs. keep decisions based on total cost.

---

## 5. End-to-End Workflows

### 5.1 Asset Lifecycle & Depreciation

1. **Register asset** via the Asset Registry.
2. **Run depreciation** monthly via the Depreciation screen:
   - `FixedAssetService::runDepreciation()` posts GL journals and updates accumulated depreciation.
3. **Monitor depreciation**:
   - Use Depreciation schedule for forecast.
   - Use Depreciation history to audit postings.
4. **Dispose asset** when needed:
   - Status is set to `disposed` and subsequent depreciation is skipped for that asset.

### 5.2 Maintenance & Total Cost of Ownership

1. **Record maintenance** via Maintenance Cost Tracking.
2. **Review reports**:
   - See acquisition cost, accumulated depreciation, and maintenance totals per asset.
3. **Decide on replacement** or retention:
   - Based on total maintenance spend and remaining useful life.

---

## 6. Design Decisions & Guarantees

- **Straight-Line Depreciation**
  - Current implementation uses straightforward, auditable straight-line depreciation.
  - Depreciation is monthly and capped by remaining depreciable amount.

- **GL Integration via JournalService**
  - Depreciation journals are posted through `JournalService`, respecting:
    - Double-entry rules.
    - Period locking.
    - Immutability and idempotency (via `PostingSource`).

- **Asset-Centric Maintenance Tracking**
  - Maintenance is always tied to a specific asset, ensuring accurate cost attribution.

---

## 7. Recommended Enhancements

These are **optional improvements** to extend the Fixed Assets module.

### 7.1 Additional Depreciation Methods

- Add support for:
  - **Declining balance** (e.g. 150% or 200% DB).
  - **Units-of-production** for assets where usage drives depreciation.
- Allow per-asset selection of method (with audit of changes).

### 7.2 Asset Disposal Journals

- Implement disposal flow including:
  - Capture of **disposal proceeds**.
  - Automatic GL postings for:
    - Removal of asset cost and accumulated depreciation.
    - Recognition of gain/loss on disposal.

### 7.3 Integration with Operations & Fleet Systems

- For vehicle/equipment assets:
  - Integrate with fleet or maintenance systems to import usage and maintenance data.
  - Use usage data for units-of-production depreciation or cost-per-km metrics.

### 7.4 Maintenance Capitalization Rules

- Allow configuration for:
  - Capital vs. expense maintenance thresholds and rules.
  - Automatically creating **separate assets** for capitalized upgrades.

### 7.5 Asset Tagging & Dimensions

- Add optional dimensions:
  - Cost center, project, client, warehouse.
- Use them to:
  - Allocate depreciation and maintenance costs into CostingEngine and GL dimensions.

### 7.6 Reporting & Dashboards

- Provide:
  - Aging of assets by remaining useful life.
  - KPI dashboards for asset utilization and cost.
  - Export options (CSV/Excel) for external analytics.

---

## 8. Summary

The Fixed Assets module provides a structured, GL-integrated framework for:

- Maintaining a complete asset register.
- Automating straight-line depreciation with proper accounting.
- Tracking maintenance costs and supporting total cost-of-ownership reporting.

The recommended enhancements focus on depreciation method flexibility, disposal accounting, deeper integration with operational systems, and richer analytical capabilities for asset-intensive operations.

