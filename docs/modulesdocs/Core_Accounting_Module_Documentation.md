# Core Accounting Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Core Accounting** module is the foundational financial engine of LFS. It owns the **Chart of Accounts, Journal Engine, GL Posting Rules Engine, Posting Sources, and Accounting Periods**, and enforces the platform’s accounting invariants:

- Double-entry (debit = credit)
- Immutable ledger (no in-place edits; corrections via reversal)
- Period locking (no posting to closed periods)
- Source traceability for every journal (idempotent posting sources)

This document describes:

- **How the module is structured and implemented**
- **Tech stack and key components**
- **Primary features and UI flows**
- **Event-driven posting workflow**
- **Recommendations for future enhancements**

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module pattern**: Laravel modules under `app/Modules/CoreAccounting`
- **Layers**:
  - `Domain`: core rules and exceptions (e.g. `JournalImmutableException`, `PeriodLockedException`)
  - `Application`: services and event handlers (e.g. `JournalService`, `FinancialEventDispatcher`, `GLPostingEngine`, `PostingRuleResolver`, `PostingRuleValidator`, `*Handler` classes)
  - `Infrastructure`: Eloquent models and persistence (e.g. `Account`, `Journal`, `JournalLine`, `PostingRule`, `PostingRuleLine`, `Period`, `PostingSource`, `ReversalLink`)
  - `UI`: controllers + Blade views for internal operator screens (accounts, journals, posting sources, periods, posting rules)
  - `API`: event entrypoints for posting financial events from WMS/LMS (`api.php`)
- **Service provider**: `CoreAccountingServiceProvider` registers a singleton `FinancialEventDispatcher` in the container.

Database objects (via migrations):

- `accounts` – Chart of Accounts hierarchy
- `journals` – journal headers
- `journal_lines` – journal lines with dimensional tags (client, shipment, route, warehouse, vehicle, project, service line, cost center)
- `posting_sources` – idempotency + source-system linkage per journal
- `reversal_links` – linkage of original ↔ reversal journals
- `periods` – accounting periods with status (open/closed) and dates
- `posting_rules` – event-to-rule headers for the GL Posting Rules Engine
- `posting_rule_lines` – debit/credit line definitions, amount source, dimension mapping, and optional resolver hints per rule line
- `account_resolvers` – dynamic account mapping entries keyed by resolver type and payload/dimension keys
- `posting_rule_conditions` – conditional expressions that control when a posting rule applies

---

## 3. Key Components

### 3.1 Eloquent Models (Infrastructure)

- `Account`
  - Stores GL account code, name, type, parent/child relationships.
  - Used by `JournalService::resolveAccount()` via `account_id` or `account_code`.

- `Journal`
  - Header for a financial transaction.
  - Fields: `journal_number`, `journal_date`, `period`, `description`, `status`, `posted_at`.
  - Relations: `lines`, `postingSource`, `reversalLinkAsOriginal`, `reversalLinkAsReversal`.

- `JournalLine`
  - Stores debit/credit lines linked to a `journal_id`.
  - Tracks all profitability dimensions: client, shipment, route, warehouse, vehicle, project, service line, cost center.

- `PostingSource`
  - Links a journal to its external origin and enforces **idempotency**.
  - Fields: `source_system`, `source_reference`, `source_type`, `event_type`, `idempotency_key`, `payload`.

- `Period`
  - Represents accounting periods with `code`, `start_date`, `end_date`, `status`, `closed_at`.
  - Helper like `isOpen()` is used by `JournalService` to block posting to closed periods.

-- `ReversalLink`
  - Stores the linkage between an original journal and its reversal.

- `PostingRule`
  - Represents a high-level GL posting rule for a specific financial event type (e.g. `shipment-delivered`, `storage-accrual`).
  - Fields: `event_type`, `description`, `is_active`.
  - Relation: `lines` provides the ordered collection of `PostingRuleLine` records.

- `PostingRuleLine`
  - Defines one debit or credit leg of a posting rule.
  - Fields: `account_id`, `entry_type` (`debit` / `credit`), `amount_source` (payload field), `dimension_source` (JSON mapping of profitability dimensions), `resolver_type` (optional dynamic account resolver hint), `sequence`.

- `AccountResolver`
  - Supports dynamic GL account resolution for rule lines.
  - Fields: `resolver_type`, `dimension_key`, `dimension_value`, `account_id`, `priority`.
  - Typical use: `resolver_type = revenue_by_service_line`, `dimension_key = service_line`, mapping values like `warehousing` or `transport` to specific revenue accounts.

- `PostingRuleCondition`
  - Stores conditional expressions for when a `PostingRule` should apply.
  - Fields: `posting_rule_id`, `field_name`, `operator`, `comparison_value`, `priority`.
  - Example: `field_name = shipment_type`, `operator = '='`, `comparison_value = subcontracted` for subcontracted vendor invoices.

### 3.2 Application Services

- `JournalService`
  - **Responsibility**: central engine for posting and reversing journals.
  - Enforces:
    - **Balanced journals**: `validateBalanced()` throws if total debit ≠ total credit.
    - **Period locking**: `assertPeriodOpenForDate()` throws `PeriodLockedException` when posting to a closed period.
    - **Immutable ledger**: no updates to existing journals; corrections are done via `reversal()`.
  - `post(array $lines, array $meta): Journal`
    - Validates lines (not empty, balanced).
    - Resolves `journal_date` and `period` (via `resolvePeriodCodeForDate()`).
    - Creates `Journal` and all `JournalLine` rows inside a DB transaction.
    - Optionally creates a `PostingSource` for idempotent event linkage.
    - Logs via `AuditService` (`journal.posted`).
  - `reversal(Journal $journal, array $meta): Journal`
    - Validates journal status.
    - Loads all lines, creates an opposite-sign set of lines, posts them as a new journal.
    - Writes a `ReversalLink` and logs `journal.reversed`.

- `GLPostingEngine`
  - **Responsibility**: configurable translation layer between financial events and journal lines.
  - For a given `event_type` and payload, it:
    - Uses `PostingRuleResolver` to load the active `PostingRule` + `PostingRuleLine`s, including any conditional rules.
    - Uses `PostingRuleValidator` to ensure the rule is usable (active, ≥2 lines, at least one debit and one credit, accounts exist).
    - Resolves `amount_source` from the payload and builds debit/credit values per line.
    - Resolves `dimension_source` JSON (e.g. `"client_id": "payload.client_id"`) into the journal line’s profitability dimensions.
    - Uses `AccountResolverService` when `resolver_type` is set on a rule line to dynamically override the GL account based on payload/dimension values (with safe fallback to the static line account).
  - Returns an array of normalized journal lines ready for `JournalService::post()`, or `null` when no active rule exists (so handlers can safely fall back to legacy logic).

- `PostingRuleResolver`
  - Locates all active rules for a given `event_type` and uses `ConditionalRuleEngine` to choose the best match for the current payload.
  - If no conditional rule matches, falls back to the first active rule (preserving existing behavior).

- `ConditionalRuleEngine`
  - Evaluates `PostingRuleCondition` expressions (`field_name`, `operator`, `comparison_value`) against the payload.
  - Supports `=`, `!=`, `>`, `<`, `IN`, `NOT IN` operators for basic enterprise rule logic.

- `AccountResolverService`
  - Resolves the effective `account_id` for a `PostingRuleLine` when `resolver_type` is configured.
  - Queries `AccountResolver` entries (e.g. `resolver_type = revenue_by_service_line`) and matches against payload/dimension values such as `service_line`.

- `FinancialEventDispatcher`
  - Maps incoming **financial events** (e.g. shipment delivered, storage accrual, vendor invoice approved, project milestone completed) to specific handler classes.
  - Resolves and invokes handlers implementing `FinancialEventHandlerInterface`.
  - Ensures that handler output (a `Journal` or null) is normalized for the caller.

-- `FinancialEvents\*Handler` classes
  - Examples:
    - `ShipmentDeliveredHandler`
    - `StorageAccrualHandler`
    - `StorageDailyAccrualHandler`
    - `PodConfirmedHandler`
    - `FreightCostAccrualHandler`
    - `FuelExpenseRecordedHandler`
    - `VendorInvoiceApprovedHandler`
    - `VendorPaymentProcessedHandler`
    - `ClientInvoiceIssuedHandler`
    - `ClientPaymentReceivedHandler`
    - `ClientCreditNoteHandler`
    - `PurchaseOrderReceivedHandler`
    - `InventoryAdjustmentHandler`
    - `AssetAcquisitionHandler`
    - `DepreciationPostingHandler`
    - `ProjectMilestoneCompletedHandler`
  - Responsibilities:
    - Validate event payload (shape and required fields) according to the Financial Event Catalog.
    - Delegate GL account and dimension mapping to `GLPostingEngine` when a posting rule exists for the event.
    - Fall back to the existing hardcoded mapping only for legacy events where rules are not yet configured (to avoid regressions).
    - Build the final line array for `JournalService::post()`.
    - Attach appropriate `source_system`, `source_reference`, `event_type`, and `idempotency_key`.

### 3.3 UI Controllers & Views

- `CoreAccountingController`
  - **Index**: module overview.
  - **Accounts**: list and detail views for the chart of accounts.
  - **Journals**: list and detail view; includes reversal linkage and posting source details.
  - **Posting sources**: monitor incoming financial events and their resulting journals.
  - **Posting rules**: list, create, and edit GL posting rules (event type, description, active flag, rule lines, dimension mappings).
  - **Periods**:
    - `periods.index`: view existing periods (with sorting, status).
    - `periods.close`: close a period (sets status, `closed_at`, and logs via `AuditService`).

- `FinancialEventController`
  - (In UI/API) endpoint surface for posting or reprocessing financial events using `FinancialEventDispatcher`.

### 3.4 Navigation Menus & Screens

This section explains how each **Core Accounting navigation menu** behaves from a user’s perspective.

#### Core Accounting Home Dashboard

Path: `Core Accounting → Home` (`/core-accounting`).

Cards:

- **Chart of Accounts**
  - Navigates to `/core-accounting/accounts`.
  - Entry point for **browsing the Chart of Accounts**.
- **Journal Management**
  - Navigates to `/core-accounting/journals`.
  - Entry point for **viewing journals and lines**.
- **Posting Sources**
  - Navigates to `/core-accounting/posting-sources`.
  - Used for **monitoring event ingestion and idempotency**.
- **Period Management**
  - Navigates to `/core-accounting/periods`.
  - Used for **opening/closing accounting periods**.
- **Posting Rules**
  - Navigates to `/core-accounting/posting-rules`.
  - Used for **configuring GL posting rules** that drive the GL Posting Rules Engine.

Each feature screen has a **“Back to Core Accounting”** link in the header that returns to this dashboard.

#### Chart of Accounts Menu

- **List page**
  - Route: `GET /core-accounting/accounts`.
  - Shows the seeded Chart of Accounts with:
    - Code, name, type (asset/liability/equity/revenue/expense), level, and whether the account is **posting** or **summary**.
  - Pagination supports large charts.
  - If no accounts are present, the UI hints to run `ChartOfAccountsSeeder`.
  - Each row has a **View** link:
    - Opens `GET /core-accounting/accounts/{id}` for account detail.

- **Account detail (show)**
  - Route: `GET /core-accounting/accounts/{id}`.
  - Displays:
    - Account metadata (code, name, type, posting flag, level).
    - Parent/child hierarchical relationships.
  - Used mainly for **read-only configuration inspection** and to confirm account structures when troubleshooting mapping issues.

#### Journal Management Menu

- **List page**
  - Route: `GET /core-accounting/journals`.
  - Shows a paginated list of journals with:
    - Journal number, date, period, description, status, and line count.
    - Each row includes a **View** link to open journal detail.
  - Typical usage:
    - Reviewing journals created by financial events (e.g. shipments, vendor invoices).
    - Verifying that periods and descriptions are correct.

- **Journal detail**
  - Route: `GET /core-accounting/journals/{id}`.
  - Displays:
    - Header: journal number, date, period, status, posted timestamp.
    - Lines: accounts, debits, credits, and attached dimensions (client, shipment, etc.).
    - Posting source: which event and system created the journal.
    - Reversal links: original ↔ reversal journals, where applicable.
  - Primary use:
    - **Audit and troubleshooting** for postings coming from AR/AP, Procurement (see `Procurement_Module_Documentation.md`), and other modules.

#### Posting Sources Menu

- **List page**
  - Route: `GET /core-accounting/posting-sources`.
  - Shows the **event ingestion log** for Core Accounting:
    - ID, event type, source system, source reference, linked journal, and created timestamp.
  - If a `journal_id` is present:
    - A link to the journal detail (`core-accounting.journals.show`) is displayed.
  - If a journal has not yet been created:
    - The row is marked as **Pending**.
  - Primary use:
    - Investigate **which events created which journals**, and confirm that idempotency keys are functioning correctly.

#### Period Management Menu

- **List page**
  - Route: `GET /core-accounting/periods`.
  - Shows all defined periods with:
    - Code, start date, end date, status (`open` / `closed`), and `closed_at` timestamp if applicable.
  - For users with `core-accounting.manage` permission:
    - An **Actions** column provides a **Close** button for open periods.

- **Closing a period**
  - Action: `POST /core-accounting/periods/{id}/close`.
  - Behavior:
    - If the period is already closed, the controller shows an error message.
    - When closing succeeds:
      - Status is set to `closed`, `closed_at` is populated.
      - A financial audit log entry is written via `AuditService` (“Period closed: …”).
  - Effect:
    - `JournalService::assertPeriodOpenForDate()` then blocks any new postings or reversals into that date range, implementing **hard period locking**.

#### Posting Rules Menu

- **List page**
  - Route: `GET /core-accounting/posting-rules`.
  - Shows all configured posting rules with:
    - Event type, description, active flag, and line count.
  - Primary use:
    - Quick overview of which financial events are currently driven by configurable posting rules.

- **Create/Edit rule**
  - Routes:
    - `GET /core-accounting/posting-rules/create`
    - `GET /core-accounting/posting-rules/{id}/edit`
    - `POST /core-accounting/posting-rules`
    - `PUT /core-accounting/posting-rules/{id}`
  - Requires `core-accounting.manage` permission.
  - Fields:
    - `event_type` (e.g. `shipment-delivered`, `storage-accrual`, `vendor-invoice-approved`, `project-milestone-completed`).
    - `description` and `is_active`.
    - One or more rule lines:
      - `account_id` (posting account from the Chart of Accounts).
      - `entry_type` (`debit` / `credit`).
      - `amount_source` (payload field name, typically `amount`).
      - Dimension checkboxes (e.g. client, shipment, route, warehouse, vehicle, project, service line, cost center) that map to payload fields such as `payload.client_id`.
      - Optional `resolver_type` hint (e.g. `revenue_by_service_line`) that activates dynamic account resolution via `AccountResolver`.
     - Optional conditions:
       - Each condition row has `field_name` (payload key), `operator`, and `comparison_value`.
       - Used to apply this rule only when certain business criteria are met (e.g. `shipment_type = subcontracted`).
  - Effect:
    - Changes take effect immediately for subsequent financial events and are consumed by the `GLPostingEngine`, including conditional rule selection and dynamic account resolution.

---

## 4. Core Workflows

### 4.1 Event-Driven Posting Workflow

1. **Operational event occurs** in WMS/LMS  
   Examples aligned with the Financial Event Catalog:
   - `shipment_delivered`
   - `storage_accrual`
   - `storage_daily_accrual`
   - `pod_confirmed`
   - `freight_cost_accrual`
   - `fuel_expense_recorded`
   - `vendor_invoice_approved`
   - `vendor_payment_processed`
   - `client_invoice_issued`
   - `client_payment_received`
   - `client_credit_note`
   - `purchase_order_received`
   - `inventory_adjustment`
   - `asset_acquisition`
   - `depreciation_posting`
   - `project_milestone_completed`

2. **WMS/LMS calls LFS API**  
   - Endpoint: `POST /api/financial-events/{event_type}`  
   - Payload includes: operational reference (shipment ID, vendor invoice ID, project ID), amounts, dimensional data, idempotency key.

3. **FinancialEventDispatcher resolution**
   - Dispatcher chooses the appropriate handler based on `{event_type}`.
   - Passes payload + context (tenant, environment, correlation IDs).

4. **Event handler builds journal lines (via GL Posting Rules Engine)**
   - Validates business rules (required fields, allowed transitions).
   - Calls `GLPostingEngine::buildJournal(event_type, payload)` to translate the event into debit/credit lines using `posting_rules` and `posting_rule_lines`.
   - If no active rule exists for the event type, falls back to the existing inline mapping logic to ensure backward compatibility.
   - Produces a normalized list of debit/credit lines for `JournalService::post()`.

5. **JournalService::post()**
   - Ensures **balance** and checks **period open**.
   - Creates journal header and lines atomically (DB transaction).
   - Persists `PostingSource` for idempotency and traceability.
   - Writes to `AuditService`.

6. **Downstream modules react**
   - `JournalPosted` event is dispatched.
   - AR/AP modules listen and create invoice/bill lines, vouchers, etc., as required.

### 4.2 Manual & Back-Office Workflows

- **Chart of Accounts**
  - Accounts are typically seeded via `ChartOfAccountsSeeder`.
  - UI screens allow viewing and navigating parent/child relationships.

- **Period Management**
  - Finance can view periods and close them when reconciled.
  - Once closed, `JournalService` blocks any new postings or reversals into that date range.

- **Journal Monitoring**
  - Journals index provides chronological list with line counts.
  - Detail view includes lines, dimensions, posting source, and reversal relationships.

- **Posting Rules Management**
  - Default rules are seeded via `PostingRulesSeeder` to mirror the legacy hardcoded postings for common events (`shipment-delivered`, `storage-accrual`, `vendor-invoice-approved`, `project-milestone-completed`).
  - Seeder also demonstrates enterprise patterns:
    - Dynamic revenue-by-service-line mapping for `shipment-delivered` using `AccountResolver` entries (e.g. warehousing → storage revenue, transport → freight revenue, project cargo → project revenue).
    - A conditional rule for `vendor-invoice-approved` when `shipment_type = subcontracted`, posting to cost-of-freight accounts instead of standard transport expense.
  - Finance users with `core-accounting.manage` permission can adjust these rules (accounts, amount source field, active flag, mapped dimensions, resolver hints, and basic conditions) through the Posting Rules UI without a code change.

---

## 5. Design Decisions & Guarantees

- **Immutability**
  - Journals are never edited in place.
  - Corrections always create a **reversal journal** that mirrors the original.

- **Period Locking**
  - `assertPeriodOpenForDate()` enforces that no postings enter closed periods.
  - This aligns with audit and compliance standards.

- **Idempotency & Traceability**
  - `PostingSource` captures `idempotency_key` and original payload.
  - Repeated events with the same key can be safely deduplicated at the handler or dispatcher layer.

- **Dimension-Rich Lines**
  - Every `JournalLine` carries profitability dimensions so that GL, AR/AP, and reporting modules can join on client, shipment, project, route, warehouse, vehicle, service line, and cost center.

---

## 6. How the Module Was Created (Build Notes)

- Implemented as a **standalone Laravel module** under `app/Modules/CoreAccounting` to keep core accounting concerns isolated.
- Database structure was introduced through a series of migrations to create `accounts`, `journals`, `journal_lines`, `posting_sources`, `reversal_links`, `periods`, `posting_rules`, `posting_rule_lines`, `account_resolvers`, and `posting_rule_conditions` tables, with indexes on high-volume columns (dates, foreign keys, dimensions).
- Application logic is concentrated in `JournalService`, the **GL Posting Rules Engine** (`GLPostingEngine`, `PostingRuleResolver`, `PostingRuleValidator`, `AccountResolverService`, `ConditionalRuleEngine`), and financial event handlers, called via `FinancialEventDispatcher`, which is registered as a singleton in `CoreAccountingServiceProvider`.
- UI routes are grouped under the `core-accounting` prefix, with `core-accounting.view` and `core-accounting.manage` permissions controlling access, including Posting Sources and Posting Rules screens.
- Integration contracts are defined in `api.php` under the module, exposing financial event endpoints for WMS/LMS and other systems.

### 6.1 Alignment with Domain Blueprint & COA Design

- The implemented Chart of Accounts follows the **LFS Chart of Accounts Master Design**:
  - Uses the 1xxx–8xxx major group structure (Assets, Liabilities, Equity, Revenue, Cost of Services, Operating Expenses, Other Income, Other Expenses).
  - Seeds logistics-native revenue and cost accounts (e.g. warehousing, transport, project logistics, value-added services, fuel, tolls, subcontracted freight) via `ChartOfAccountsSeeder`.
  - Ensures compatibility with profitability dimensions (client, shipment, route, warehouse, vehicle, project, service line, cost center) at the journal line level.
- The Core Accounting architecture is derived from the **Core Accounting Domain Blueprint (Laravel)**:
  - Domain exceptions and responsibilities match the blueprint (period governance, double-entry validation, posting source traceability, configurable posting rules).
  - Application services (`JournalService`, `FinancialEventDispatcher`, `GLPostingEngine`, rule and account resolvers) implement the recommended event → rule → journal pipeline.
  - Infrastructure repositories (`AccountRepository`, `PostingRuleRepository`, `JournalRepository`) provide the persistence access layer described in the blueprint while remaining internal implementation details.

---

## 7. Recommended Enhancements

These are **optional improvements** that can strengthen robustness, usability, and extensibility of the Core Accounting module.

### 7.1 Journal Templates & Recurring Entries

**Idea**: Add support for reusable **journal templates** and **recurring journals** (e.g. monthly allocations, fixed accruals).

- New tables: `journal_templates`, `journal_template_lines`, optionally `recurring_schedules`.
- UI: allow finance users to define a template with accounts, percentages/amounts, and dimensions.
- Scheduler: weekly/monthly job that instantiates journals from active schedules via `JournalService::post()`.

### 7.2 Stronger Idempotency & Duplicate Detection

Current implementation already enforces strong idempotency for financial events:

- `posting_sources.idempotency_key` has a unique constraint, so duplicate keys are rejected at the database level.
- `FinancialEventController` checks for an existing `PostingSource` before dispatching and returns a `duplicate` status without reposting the journal.

Further hardening (optional future work) could:

- Add an additional composite index on `(source_system, idempotency_key)` if tenant-specific idempotency is required.
- Extend `FinancialEventDispatcher` to short-circuit at the dispatcher layer based on existing posting sources or integration logs.

### 7.3 Enhanced Period Management

- Add **period open/close history** or audit records per period change (currently a single `closed_at` timestamp).
- Support **multi-period calendars** (e.g. fiscal calendar vs. calendar month) by allowing multiple concurrent period sets with tagging.

### 7.4 Journal Approval Workflow (Optional)

If required by policy, introduce an **approval step** before posting:

- New statuses on `journals`: `draft`, `pending_approval`, `posted`.
- Allow certain financial events to create **draft journals** requiring manual approval, while operationally low-risk events can auto-post.
- Add a simple approval UI filtered by period/amount/responsible role.

### 7.5 Performance & Archival Strategy

For long-term scalability:

- Implement **partitioning / archival** strategy for `journals` and `journal_lines` (e.g. yearly partitions or archive tables).
- Add configurable **retention windows** for `posting_sources.payload` (keep headers forever, payloads for X years).

### 7.6 Domain Documentation Hook

The empty `CoreAccountingOverview` class can be wired to expose:

- Programmatic summary of key stats (open periods, last journal date, journal counts by source system).
- A JSON endpoint (or internal API) to feed dashboards or automated health checks.

---

## 8. Summary

The Core Accounting module provides a **disciplined, event-driven journal engine** with:

- Strong double-entry and period controls
- Immutable ledger via reversals only
- Rich dimensional tagging
- Tight integration with AR/AP, Procurement (see `Procurement_Module_Documentation.md`), and Reporting

The recommended enhancements above are designed to preserve these guarantees while improving usability, governance, and scalability as transaction volume and complexity grow.

