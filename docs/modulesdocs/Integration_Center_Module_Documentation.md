# Integration Center Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Integration Center** is the **operations console** for all external integrations into LFS.  
It provides:

- A **single UI entry point** for monitoring:
  - Financial events received from WMS/LMS and other external systems.
  - Internal sync logs and idempotent posting sources.
- A set of **API gateways** for:
  - Ingesting financial events (REST).
  - Exposing financial and operational data to upstream/downstream systems.

The goal is to give administrators and integrators:

- **Visibility** into event flows and posting status.
- **Traceability** from external events to GL journals.
- **Protection** against duplicate processing (idempotency).

Note: The Integration Center UI lives under the **LFS Administration** module, but conceptually constitutes its own module in the platform.

---

## 2. Tech Stack & Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **UI Location**: `app/Modules/LFSAdministration` (Integration Center screens).
- **API Endpoints**: Distributed across module-level `api.php` route files:
  - `app/Modules/CoreAccounting/api.php` (financial events ingestion).
  - `app/Modules/GeneralLedger/api.php` (GL read APIs).
  - `app/Modules/BillingEngine/api.php` (billing contracts/clients/simulation).
  - `app/Modules/AccountsReceivable/api.php` (AR API surface – placeholder).
  - `app/Modules/AccountsPayable/api.php` (AP API surface – placeholder).
  - `app/Modules/Treasury/api.php` (Treasury API surface – placeholder).
  - `app/Modules/InventoryValuation/api.php`, `FixedAssets/api.php`, `FinancialReporting/api.php`, `LFSAdministration/api.php` – placeholders for future expansion.
- **Integration Center UI Controller**:
  - `LFSAdministrationController` methods:
    - `integrationEvents()` – Financial Events Monitor.
    - `syncLogs()` – Sync Logs.
- **Supporting Models**:
  - `IntegrationLog` – records incoming integration events and their processing status.
  - `PostingSource` (Core Accounting) – idempotent posting sources and link to journals.

Security & permissions:

- All APIs use **`auth:sanctum`** for token-based authentication.
- Financial events API additionally uses **`permission:integration.financial-events`**.
- Integration Center UI routes:
  - Guarded by `permission:lfs-administration.view`.

---

## 3. Inbound & Outbound Integration APIs

### 3.1 Financial Events API (Inbound)

Defined in `app/Modules/CoreAccounting/api.php`:

- Route group:
  - Middleware: `auth:sanctum`, `permission:integration.financial-events`.
  - Prefix: `/api/financial-events`.
  - Name: `api.financial-events.*`.

- Endpoint:
  - `POST /api/financial-events/{event_type}` → `FinancialEventController::__invoke`.
  - Constraints:
    - `event_type` pattern: `[-a-zA-Z0-9_]+` (e.g. `shipment-delivered`, `storage-accrual`, `vendor-invoice-approved`).

Workflow:

1. External system (e.g. WMS/LMS) posts JSON payload with:
   - Operational reference IDs (shipment, vendor invoice, project, etc.).
   - Monetary amounts, dimensions, idempotency key, source system.
2. `FinancialEventController`:
   - Uses `FinancialEventDispatcher` to route to the correct handler.
   - Handlers post journals via `JournalService`, creating `PostingSource` entries.
   - AR/AP modules listen for `JournalPosted` events to create invoice/bill lines.
3. Integration Center:
   - Logs event outcome into `IntegrationLog` (posted, accepted, duplicate, error).

### 3.2 Data & Service APIs (Outbound)

Example modules:

- **General Ledger API** (`app/Modules/GeneralLedger/api.php`)
  - Prefix: `/api/general-ledger`.
  - Endpoints:
    - `GET /trial-balance` – `GeneralLedgerApiController@trialBalance`.
    - `GET /ledger` – `GeneralLedgerApiController@ledger`.
    - `GET /accounts` – `GeneralLedgerApiController@accounts`.
  - Use cases:
    - External BI tools, consolidation engines, or reporting services retrieving GL data.

- **Billing Engine API** (`app/Modules/BillingEngine/api.php`)
  - Prefix: `/api/billing-engine`.
  - Endpoints:
    - `GET /clients` – list billable clients.
    - `GET /contracts` – list billing contracts.
    - `GET|POST /simulate` – **rating simulation** endpoint for “what-if” scenarios.
  - Use cases:
    - WMS/LMS or external pricing tools querying contract/rate data.

- **Other module APIs**
  - AR, AP, Treasury, Inventory, Fixed Assets, Financial Reporting, LFS Administration:
    - Structured as empty groups ready for future endpoints (with `auth:sanctum` and module-specific prefixes).

These APIs, combined with Financial Events, form the **Integration Center’s programmatic surface** for external systems.

---

## 4. Integration Center UI: Menus & Screens

Integration Center appears under **LFS Administration** in the sidebar (`config/navigation.php`):

- `label`: **Integration Center**
- `icon`: `fas fa-plug`
- `permission`: `lfs-administration.view`
- Children:
  - **Financial Events Monitor**
  - **Sync Logs**

### 4.1 Financial Events Monitor

- Route: `GET /lfs-administration/integration-events` → `integrationEvents()`.
- Permissions: `lfs-administration.view`.
- Purpose:
  - Console to view **all incoming integration events**, their status, and related journals.

Filter form:

- `Event type` – text filter (e.g. `shipment-delivered`, `vendor-invoice-approved`).
- `Status` – dropdown:
  - `posted`, `accepted`, `duplicate`, `error`, or all.
- `From date`, `To date` – date range on `created_at`.

Result table:

- Columns:
  - **Date** – when the event was logged.
  - **Event type** – event identifier.
  - **Source system** – e.g. `wms`, `lms`, or external integration name.
  - **Reference** – operational reference (e.g. shipment or invoice ID).
  - **Status**:
    - Color-coded chips:
      - Posted (green), Accepted (blue), Duplicate (yellow), Error (red).
  - **Journal**:
    - Link to `core-accounting.journals.show` when a journal was created.
  - **Message**:
    - Any error or informational message (truncated with tooltip).

Use cases:

- Quickly see:
  - Which events are **failing** (error) or being **deduplicated**.
  - Which events have successfully posted journals.
- Troubleshoot:
  - Missing or malformed payloads.
  - Idempotency conflicts and duplicate attempts.

### 4.2 Sync Logs

- Route: `GET /lfs-administration/sync-logs` → `syncLogs()`.
- Permissions: `lfs-administration.view`.
- Purpose:
  - View **`PostingSource`** records that represent successful (or attempted) journal postings from integration and internal events.

Filter form:

- `Source system` – free-text (e.g. `wms`, `lms`, `fixed-assets`).
- `Event type` – free-text (e.g. `depreciation`, `vendor-invoice-approved`).
- `From date`, `To date` – filter by `created_at`.

Result table:

- Columns:
  - **Date** – posting source creation timestamp.
  - **Event type** – logical event type (e.g. `depreciation`, `shipment-delivered`).
  - **Source system** – origin system name.
  - **Reference** – external or internal reference key.
  - **Idempotency key** – unique key used to prevent duplicates (truncated with full value as tooltip).
  - **Journal** – link to journal (number) if present; otherwise placeholder.

Use cases:

- Confirm that:
  - Events have been posted **exactly once**.
  - The correct GL journals were created for a given idempotency key.
- Provide a **technical audit trail** for integrations and postings.

---

## 5. End-to-End Integration Workflows

### 5.1 WMS/LMS → LFS Financial Event Flow

1. **Operational event** (e.g. shipment delivered) occurs in WMS/LMS.
2. WMS/LMS posts to:
   - `POST /api/financial-events/shipment-delivered` with payload:
     - Shipment ID, client ID, amounts, dimensions, idempotency key, source system.
3. LFS:
   - `FinancialEventDispatcher` invokes the correct handler.
   - Handler posts journal via `JournalService`, creating a `PostingSource`.
   - AR/AP modules listen and create invoice/bill lines as needed.
4. Integration Center:
   - `IntegrationLog` is written (event status, message, related journal).
   - `Financial Events Monitor` UI displays the event and status.
   - `Sync Logs` shows the corresponding `PostingSource` with idempotency key and journal.

### 5.2 External BI Tool → LFS Data Retrieval

1. BI tool authenticates via **Sanctum API token**.
2. It calls:
   - `GET /api/general-ledger/trial-balance` for GL snapshots.
   - `GET /api/general-ledger/ledger` or `/accounts` for detailed queries.
   - Optional: `GET /api/billing-engine/clients` and `/contracts` to enrich data with contract and client dimensions.
3. Integrators monitor:
   - `Sync Logs` for GL postings tied to external systems.
   - Integration logs when events are feeding GL via automated jobs.

---

## 6. Design Decisions & Guarantees

- **Event-Driven Architecture**
  - Financial events push from operational systems via `financial-events` API, keeping LFS decoupled from upstream systems.

- **Idempotency**
  - `PostingSource.idempotency_key` ensures **no duplicate journals** are posted for the same event.
  - `Sync Logs` makes idempotency tracking visible for operators.

- **Role-Based Access**
  - API and UI access separated:
    - Integrations use tokens + integration-specific permissions.
    - Operators use `lfs-administration.view` to monitor.

- **Central Visibility, Distributed APIs**
  - APIs live with their domains (GL, Billing, Treasury, etc.).
  - Integration Center **aggregates monitoring** across domains.

---

## 7. Recommended Enhancements

These are **optional improvements** that could make the Integration Center more powerful.

### 7.1 Retry & Dead-Letter Queues

- Extend `IntegrationLog` with:
  - Structured error codes and retry counts.
  - Actions:
    - **Retry** failed events from the UI.
    - Move persistent failures to a **dead-letter queue** for manual review.

### 7.2 API Catalog & Documentation

- Add an **API Catalog** screen under Integration Center:
  - List all available `/api/...` endpoints, methods, and brief descriptions.
  - Include example payloads and response schemas.
- Optionally integrate with OpenAPI/Swagger documentation generation.

### 7.3 Real-Time Monitoring & Alerts

- Integrate with:
  - Notification channels (email/Slack) for:
    - High failure rates.
    - Large spikes in duplicate events.
  - Real-time dashboards with:
    - Event throughput.
    - Error rate.

### 7.4 Mapping & Transformation Rules

- Centralize configuration for:
  - **Field mappings** between external payloads and internal structures.
  - **Transformation rules** (e.g. mapping external codes to accounts or dimensions).
- UI to manage mapping rules with versioning and audit.

### 7.5 Bulk Import & Export Tools

- Tools for:
  - Bulk import of **historical events** or master data (e.g. via CSV).
  - Export of integration logs and sync logs for offline analysis.

### 7.6 Health Checks & Connectivity Status

- Show status of:
  - Integration endpoints (up/down).
  - Scheduled jobs and queues related to integrations.
- Provide quick diagnostics for integrators when connections break.

---

## 8. Summary

The Integration Center module provides:

- A unified view of **incoming financial events** and **posting sources**.
- A secure, token-based **API surface** for operational systems and BI tools.
- Strong **idempotency guarantees** and traceability from events to GL journals.

The recommended enhancements focus on retry mechanisms, richer documentation, real-time monitoring, mapping configuration, and system health to further strengthen operational robustness and integrator experience.

