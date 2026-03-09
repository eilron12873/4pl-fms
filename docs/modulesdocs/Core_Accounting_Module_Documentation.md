# Core Accounting Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Core Accounting** module is the foundational financial engine of LFS. It owns the **Chart of Accounts, Journal Engine, Posting Sources, and Accounting Periods**, and enforces the platform’s accounting invariants:

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
  - `Application`: services and event handlers (e.g. `JournalService`, `FinancialEventDispatcher`, `*Handler` classes)
  - `Infrastructure`: Eloquent models and persistence (e.g. `Account`, `Journal`, `JournalLine`, `Period`, `PostingSource`, `ReversalLink`)
  - `UI`: controllers + Blade views for internal operator screens (accounts, journals, posting sources, periods)
  - `API`: event entrypoints for posting financial events from WMS/LMS (`api.php`)
- **Service provider**: `CoreAccountingServiceProvider` registers a singleton `FinancialEventDispatcher` in the container.

Database objects (via migrations):

- `accounts` – Chart of Accounts hierarchy
- `journals` – journal headers
- `journal_lines` – journal lines with dimensional tags (client, shipment, route, warehouse, vehicle, project, service line, cost center)
- `posting_sources` – idempotency + source-system linkage per journal
- `reversal_links` – linkage of original ↔ reversal journals
- `periods` – accounting periods with status (open/closed) and dates

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

- `ReversalLink`
  - Stores the linkage between an original journal and its reversal.

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

- `FinancialEventDispatcher`
  - Maps incoming **financial events** (e.g. shipment delivered, storage accrual, vendor invoice approved, project milestone completed) to specific handler classes.
  - Resolves and invokes handlers implementing `FinancialEventHandlerInterface`.
  - Ensures that handler output (a `Journal` or null) is normalized for the caller.

- `FinancialEvents\*Handler` classes
  - Examples: `ShipmentDeliveredHandler`, `StorageAccrualHandler`, `VendorInvoiceApprovedHandler`, `ProjectMilestoneCompletedHandler`.
  - Responsibilities:
    - Validate event payload.
    - Derive the correct GL accounts and dimensions.
    - Build the line array for `JournalService::post()`.
    - Attach appropriate `source_system`, `source_reference`, `event_type`, and `idempotency_key`.

### 3.3 UI Controllers & Views

- `CoreAccountingController`
  - **Index**: module overview.
  - **Accounts**: list and detail views for the chart of accounts.
  - **Journals**: list and detail view; includes reversal linkage and posting source details.
  - **Posting sources**: monitor incoming financial events and their resulting journals.
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

---

## 4. Core Workflows

### 4.1 Event-Driven Posting Workflow

1. **Operational event occurs** in WMS/LMS  
   Example: `shipment_delivered`, `storage_accrual`, `vendor_invoice_approved`, `project_milestone_completed`.

2. **WMS/LMS calls LFS API**  
   - Endpoint: `POST /api/financial-events/{event_type}`  
   - Payload includes: operational reference (shipment ID, vendor invoice ID, project ID), amounts, dimensional data, idempotency key.

3. **FinancialEventDispatcher resolution**
   - Dispatcher chooses the appropriate handler based on `{event_type}`.
   - Passes payload + context (tenant, environment, correlation IDs).

4. **Event handler builds journal lines**
   - Validates business rules (required fields, allowed transitions).
   - Maps to GL accounts and dimensions.
   - Constructs a list of debit/credit lines for `JournalService::post()`.

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
- Database structure was introduced through a series of migrations to create `accounts`, `journals`, `journal_lines`, `posting_sources`, `reversal_links`, and `periods` tables, with indexes on high-volume columns (dates, foreign keys, dimensions).
- Application logic is concentrated in `JournalService` and **financial event handlers**, called via `FinancialEventDispatcher`, which is registered as a singleton in `CoreAccountingServiceProvider`.
- UI routes are grouped under the `core-accounting` prefix, with `core-accounting.view` and `core-accounting.manage` permissions controlling access.
- Integration contracts are defined in `api.php` under the module, exposing financial event endpoints for WMS/LMS and other systems.

---

## 7. Recommended Enhancements

These are **optional improvements** that can strengthen robustness, usability, and extensibility of the Core Accounting module.

### 7.1 Journal Templates & Recurring Entries

**Idea**: Add support for reusable **journal templates** and **recurring journals** (e.g. monthly allocations, fixed accruals).

- New tables: `journal_templates`, `journal_template_lines`, optionally `recurring_schedules`.
- UI: allow finance users to define a template with accounts, percentages/amounts, and dimensions.
- Scheduler: weekly/monthly job that instantiates journals from active schedules via `JournalService::post()`.

### 7.2 Stronger Idempotency & Duplicate Detection

Current posting sources already store `idempotency_key`, but you can:

- Enforce **unique index** on `(source_system, idempotency_key)` in `posting_sources` to hard-block duplicates.
- Extend `FinancialEventDispatcher` to **short-circuit** if an identical posting source already exists for the same event.

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

