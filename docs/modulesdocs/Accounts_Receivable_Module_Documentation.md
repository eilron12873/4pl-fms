# Accounts Receivable (AR) Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Accounts Receivable (AR)** module manages **customer invoicing, credit notes, payments, and AR reporting** for LFS.  
It is tightly integrated with:

- **Core Accounting** (journal engine & GL)
- **Billing Engine** (contract-based rating and billable events)

The AR module supports both **automated billing** (from operational events and rating) and **manual AR entry** (back-office invoice creation/editing), while enforcing:

- Clear separation between **draft** and **issued** invoices
- Proper AR journal postings (DR AR, CR Revenue)
- Accurate client balances and AR aging

This document covers:

- **Module structure and tech stack**
- **Key models, services, and controllers**
- **End-to-end workflows (automated + manual)**
- **Reporting capabilities (Statement of Account, Aging)**
- **Enhancement recommendations**

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/AccountsReceivable`
- **Layers**:
  - `Domain`: AR domain root (`AccountsReceivable`) and AR-specific rules.
  - `Application`: `InvoiceService`, `ArReportingService`, `AccountsReceivableOverview`, event listeners.
  - `Infrastructure`: Eloquent models (`ArInvoice`, `ArInvoiceLine`, `ArInvoiceAdjustment`, `ArPayment`, `ArInvoicePayment`) and repository.
  - `UI`: controllers + Blade views (invoices, payments, AR index, statement, aging).
  - `API`: AR-related API contracts and entrypoints (`api.php`) used by other modules.
- **Service provider**: `AccountsReceivableServiceProvider` registers module services and routes (via Laravel module bootstrap).

Database objects (via AR migrations):

- `ar_invoices` – invoice headers per client.
- `ar_invoice_lines` – invoice line items, optionally linked to journals and shipments.
- `ar_invoice_adjustments` – credit notes / adjustments.
- `ar_payments` – customer payments (header).
- `ar_invoice_payments` – allocations from payments to invoices.

---

## 3. Key Components

### 3.1 Eloquent Models (Infrastructure)

- `ArInvoice`
  - Fields: `client_id`, `invoice_number`, `invoice_date`, `due_date`, `status`, `subtotal`, `tax_amount`, `total`, `amount_allocated`, `currency`, `notes`, `journal_id`.
  - Casts: dates and monetary fields (decimal).
  - Relations:
    - `client` → `BillingClient`
    - `lines` → `ArInvoiceLine`
    - `adjustments` → `ArInvoiceAdjustment`
    - `invoicePayments` → `ArInvoicePayment`
  - Helpers:
    - `balance_due` accessor (`total - amount_allocated`).
    - `isIssued()` (true for `issued`, `partially_paid`, `paid`).
    - `isPaid()` (status `paid`).

- `ArInvoiceLine`
  - Line items for invoices: description, quantity, unit_price, amount.
  - Optional relations to Core Accounting and operations: `journal_id`, `source_type`, `source_reference`, `shipment_id`, `client_id`.

- `ArInvoiceAdjustment`
  - Represents credit notes and adjustments linked to an invoice.
  - Tracks type/amount/reason for audit and reporting.

- `ArPayment`
  - Customer payment header: `client_id`, `payment_date`, `amount`, `currency`, `reference`, `notes`.

- `ArInvoicePayment`
  - Allocation of a payment to one or more invoices: `payment_id`, `invoice_id`, `amount`.
  - Enables partial allocations across multiple invoices.

### 3.2 Application Services

#### InvoiceService

Central AR service for **creating, editing, issuing, and settling invoices**.

- **Event-driven line creation**
  - `createInvoiceLineFromJournal(array $context): ArInvoiceLine`
    - Called after a **billable journal** is posted.
    - Finds or creates a **draft invoice** for the client in the current month.
    - Creates an invoice line linked to `journal_id`, `source_type`, `source_reference`, and optionally `shipment_id`.
    - Recalculates totals on the invoice.

- **Manual AR Entry**
  - `createManualInvoice(array $input): ArInvoice`
    - Used by the AR Entry UI to create a **draft invoice** with manual lines.
    - Validates that at least one line exists.
    - Uses `BillingClient` currency by default, but allows override.
  - `updateDraftInvoice(ArInvoice $invoice, array $input): ArInvoice`
    - Allows editing header (client, dates, currency, notes) and lines **only while status is `draft`**.
    - Replaces lines atomically and recalculates totals.

- **Billing Engine Integration**
  - `createInvoiceFromBilling(array $input): ArInvoice`
    - Invokes `RatingService::rate(event_type, payload)` to derive billable lines.
    - Builds a draft invoice from rating output for contract-based billing flows.

- **Issuing Invoices**
  - `issueInvoice(ArInvoice $invoice, array $accountCodes = []): void`
    - No-op if already issued.
    - Posts the AR journal via `JournalService::post()`:
      - Default accounts: `receivable` = `1100`, `revenue` = `4100` (overridable via `$accountCodes`).
      - Journal description and number (`AR-INV-{invoice_number}`).
    - Stores `journal_id` on the invoice and updates status to `issued`.

- **Payments & Credit Notes**
  - `recordPayment(array $input): ArPayment`
    - Creates `ArPayment` and related `ArInvoicePayment` allocations.
    - Updates `amount_allocated` and invoice statuses (e.g. partially paid, paid).
    - Optionally posts the cash/AR journal via `JournalService` (implementation continues beyond snippet).
  - `createCreditNote(ArInvoice $invoice, float $amount, string $reason = '')`
    - Writes an `ArInvoiceAdjustment` and updates invoice allocations/totals.
    - Used from the invoice detail UI to handle discounts, write-offs, and corrections.

#### ArReportingService

Provides AR **statement** and **aging** logic for UI and exports.

- `statementOfAccount(int $clientId, ?string $fromDate, ?string $toDate): array`
  - Returns:
    - `client` (BillingClient)
    - `invoices` (with lines, adjustments, payments)
    - `payments`
    - `balance` (total outstanding)

- `agingReport(?string $asOfDate): Collection`
  - Produces **bucketed aging** per client:
    - `current`, `days_30`, `days_60`, `days_90`, `over_90`, and `total`
  - Considers only invoices in `issued` and `partially_paid` statuses with positive balances.

### 3.3 Controllers & Routes (UI Layer)

#### Routes

Defined in `app/Modules/AccountsReceivable/routes.php` (all under `accounts-receivable` prefix with `accounts-receivable.view` / `accounts-receivable.manage` permissions):

- Dashboards:
  - `GET /` → AR index.
- Invoices:
  - `GET /invoices` – list + filters (client, status).
  - `GET /invoices/create` – manual AR entry screen (create draft invoice).
  - `POST /invoices` – store draft invoice via `InvoiceService::createManualInvoice`.
  - `GET /invoices/{id}` – invoice detail.
  - `GET /invoices/{id}/edit` – edit draft invoice only.
  - `PUT /invoices/{id}` – update draft invoice via `updateDraftInvoice`.
  - `POST /invoices/{id}/issue` – issue invoice (post AR journal, set status).
  - `POST /invoices/{id}/credit-note` – create a credit note / adjustment.
- Reporting:
  - `GET /statement` – Statement of Account by client/date range.
  - `GET /aging` – AR Aging as-of a given date.
- Payments:
  - `GET /payments` – list payments with client filter.
  - `GET /payments/create` – payment entry screen.
  - `POST /payments` – record payment + allocations.

#### AccountsReceivableController

Coordinates user interactions with `InvoiceService` and `ArReportingService`:

- **Invoice lifecycle**
  - `invoices()` – paginated list, with filters and active clients for dropdowns.
  - `invoiceCreate()` / `invoiceStore()` – manual AR entry (draft invoices).
  - `invoiceEdit()` / `invoiceUpdate()` – edit draft invoice; rejects edits when `isIssued()`.
  - `invoiceShow()` – displays header, lines, adjustments, and allocation/balance context.
  - `issueInvoice()` – calls `InvoiceService::issueInvoice()` and redirects back.

- **Reporting**
  - `statement()` – orchestrates `ArReportingService::statementOfAccount()`, passing client and date range from request.
  - `aging()` – calls `agingReport()` and passes `asOfDate` to the view.

- **Payments**
  - `payments()` – list of `ArPayment` with client filter.
  - `paymentCreate()` / `paymentStore()` – capture payments and invoice allocations.
  - `creditNoteStore()` – form handler to create a credit note for an invoice; validates max amount against `balance_due`.

### 3.4 Navigation Menus & Screens

This section describes how the **AR navigation cards and menus** behave from an end‑user perspective.

#### AR Home Dashboard (`Accounts Receivable` index)

Path: `Accounts Receivable → Home` (`/accounts-receivable`).

Cards:

- **Invoices**
  - Opens the **Invoices list** (`/accounts-receivable/invoices`).
  - Entry point for:
    - Searching and filtering invoices.
    - Creating new invoices.
    - Drilling into invoice details to issue or credit.
- **Statement of Account**
  - Opens the **Statement of Account** screen (`/accounts-receivable/statement`).
  - Used for client-specific AR summaries (invoices + payments).
- **AR Aging**
  - Opens the **AR Aging** report (`/accounts-receivable/aging`).
  - Shows outstanding balances by age bucket.
- **Payments**
  - Opens the **Payments list** (`/accounts-receivable/payments`).
  - Used for payment capture and collection review.

#### Invoices Menu

- **List page**
  - Route: `GET /accounts-receivable/invoices`.
  - Shows a paginated grid of invoices with:
    - Invoice number, client, dates (invoice & due), total, balance, status.
    - `View` action per row to open the invoice detail.
  - **Filters**:
    - `Client` dropdown: limits to a single client.
    - `Status` dropdown: `draft`, `issued`, `partially_paid`, `paid`, or all.
  - **Create invoice** button:
    - Visible to users with `accounts-receivable.manage`.
    - Opens the **Create invoice** form for manual AR entry.

- **Create invoice form**
  - Route: `GET /accounts-receivable/invoices/create`.
  - Fields:
    - Client selector (BillingClient).
    - Invoice date, due date, currency.
    - Notes.
    - Lines table (description + amount) with Add/Remove line behavior.
  - On submit:
    - `POST /accounts-receivable/invoices` validates input and calls `createManualInvoice()`.
    - On success, redirects to the new invoice’s **detail view** in `draft` status.

- **Invoice detail view**
  - Route: `GET /accounts-receivable/invoices/{id}`.
  - Shows:
    - Header: client, invoice/due dates, status, currency, total, balance.
    - Lines: description, quantity, unit price, amount, and journal links (if applicable).
    - Adjustments (credit notes) and payments allocated.
  - Actions (depending on status and permissions):
    - **Edit invoice** (only when `status = draft` and user can manage):
      - Opens `GET /accounts-receivable/invoices/{id}/edit`.
    - **Issue invoice**:
      - Posts AR journal via `issueInvoice()` and sets status to `issued`.
    - **Create credit note**:
      - Form section to enter amount and reason.
      - Submits to `POST /accounts-receivable/invoices/{id}/credit-note`.

#### Statement of Account Menu

- Route: `GET /accounts-receivable/statement`.
- **Filter form**:
  - Required `Client` dropdown.
  - Optional `From date` and `To date` (date range).
  - `View` button reloads the page with the selected filters.
- **Results section** (once a client is selected):
  - Summary header: client code/name and outstanding balance.
  - **Invoices table**:
    - Invoice number (links to invoice detail), date, total, allocated, balance, status.
  - **Payments table**:
    - Payment date, amount, currency, and reference.
- Primary use:
  - Provide client-facing or internal **Statement of Account** for collections and reconciliation.

#### AR Aging Menu

- Route: `GET /accounts-receivable/aging`.
- **Filter form**:
  - `As of date` (defaults to today).
  - `Apply` button recomputes aging buckets as of the selected date.
- **Aging table**:
  - One row per client with:
    - Client code + name.
    - Buckets: `Current`, `1–30 days`, `31–60 days`, `61–90 days`, `Over 90`, and `Total`.
- Primary use:
  - High-level view of **overdue receivables** to drive collection priorities and management reporting.

#### Payments Menu

- **List page**
  - Route: `GET /accounts-receivable/payments`.
  - Shows payments with:
    - Payment date, client, amount, reference.
  - **Filter**:
    - `Client` dropdown to show payments for a specific customer.
  - Pagination for large volumes.

- **Record payment form**
  - Route: `GET /accounts-receivable/payments/create` (requires `accounts-receivable.manage`).
  - Fields:
    - Client, payment date, amount, currency, reference, notes.
    - Optional **allocations** table to assign payment amounts to specific invoices.
  - On submit:
    - `POST /accounts-receivable/payments` calls `recordPayment()` in `InvoiceService`.
    - Creates an `ArPayment` plus `ArInvoicePayment` records for each allocation and updates invoice balances.

---

## 4. End-to-End Workflows

### 4.1 Automated Billing from Operational Events

1. **Operational event in WMS/LMS**  
   Example: shipment delivered, storage day elapsed, project milestone completed.

2. **Financial event posted to Core Accounting**  
   - WMS/LMS calls `POST /api/financial-events/{event_type}`.
   - Core Accounting creates a balanced journal (e.g. DR AR Accrued, CR Revenue).

3. **AR line creation from journal**  
   - An AR listener (`RecordInvoiceLineFromJournal`) receives `JournalPosted`.
   - It calls `InvoiceService::createInvoiceLineFromJournal()` with:
     - `client_id`, `journal_id`, `amount`, `description`, `source_reference`, `source_type`, `invoice_date`, etc.
   - Service finds/creates a **draft AR invoice** for that client and month.
   - A new `ArInvoiceLine` is created and totals are recalculated.

4. **Invoice review & issue**  
   - Finance reviews the draft invoice in the UI (lines, client, dates).
   - When ready, they click **Issue invoice**, which:
     - Posts the **final AR journal** (DR AR, CR Revenue) if not already linked.
     - Locks the invoice against further edits and sets `status = issued`.

### 4.2 Manual AR Entry (Back-Office Invoicing)

1. Finance navigates to `Accounts Receivable → Invoices → Create`.
2. They select **client**, **invoice/due dates**, **currency**, optional **notes**, and add manual lines (description + amount).
3. On save:
   - `invoiceStore()` validates and passes the payload to `createManualInvoice()`.
   - A **draft invoice** is created, with lines and totals computed.
4. While in draft:
   - They may **edit** the invoice (header, lines) via `invoiceEdit()` / `invoiceUpdate()`.
5. Once final:
   - They issue the invoice, which posts the AR journal and moves it to `issued` status.

### 4.3 Payments & Credit Notes

- **Payments**
  - Finance records a payment with date, amount, currency, and optional reference.
  - They allocate the payment amount across one or more invoices.
  - `recordPayment()` creates `ArPayment` and `ArInvoicePayment` records and updates `amount_allocated` and statuses.

- **Credit Notes / Adjustments**
  - From invoice detail, finance can create a **credit note** up to the `balance_due`.
  - `createCreditNote()` records an adjustment row and reduces `balance_due`.
  - In GL, credit notes can be mapped to appropriate revenue/discount accounts via companion journals from Core Accounting (depending on implementation).

### 4.4 AR Reporting Workflows

- **Statement of Account**
  - User chooses client and date range.
  - Controller calls `statementOfAccount()`; view renders:
    - Opening/closing balances (implied by invoices and payments in range).
    - Detailed invoice and payment history per client.

- **Aging Report**
  - User selects “as of” date (or uses today).
  - Aging buckets show how much each client owes by time overdue.
  - Used for collection prioritization and management reports.

---

## 5. Design Decisions & Guarantees

- **Draft vs. Issued separation**
  - Draft invoices are fully editable (header + lines).
  - Issued invoices are **locked**; any change must use credit notes or additional invoices.

- **Integration with Core Accounting**
  - AR never posts directly to GL tables; it calls `JournalService::post()`.
  - Ensures double-entry and period locks are respected.

- **Client-Centric Design**
  - All invoices, payments, and adjustments are centered around `BillingClient`.
  - Reporting (`statementOfAccount`, `agingReport`) is structured by client to support collections.

- **Month-Based Draft Grouping (for auto lines)**
  - `createInvoiceLineFromJournal()` groups lines by client and current month into a single draft invoice, avoiding invoice spam for high-volume events.

---

## 6. Recommended Enhancements

These are **optional improvements** that can enhance robustness, usability, and analytical power of the AR module.

### 6.1 Configurable Invoice Number Sequences

Currently, invoice numbers are generated internally via `generateInvoiceNumber()` logic in `InvoiceService` (not shown in snippet). Consider:

- Supporting **per-entity / per-client / per-segment sequences**.
- Allowing configuration like prefixes (`INV-`, `AR-`, per branch) and reset periods (annual/monthly).

### 6.2 Advanced Payment Allocation & Overpayments

- Add explicit handling for **overpayments** and **unapplied cash**:
  - Track unallocated balances on `ArPayment`.
  - UI to later allocate remaining amounts as new invoices are issued.
- Introduce **allocation strategies**:
  - Oldest invoice first, by due date, or manual override.

### 6.3 Dispute & Collections Workflow

- Extend invoice statuses and metadata to support:
  - `disputed`, `on_hold`, `written_off`.
  - Dispute reason codes and notes.
- Simple workflow screens:
  - Mark invoice as disputed, record expected resolution date, and responsible owner.

### 6.4 Tax & Multi-Currency Enhancements

- If needed for your deployment:
  - Add a dedicated **tax breakdown** table or fields on `ArInvoiceLine`.
  - Support **functional vs. transaction currency**:
    - Store exchange rates and show realized FX gains/losses via Core Accounting.

### 6.5 DSO & Collection KPIs

- Extend `ArReportingService` to compute:
  - **Days Sales Outstanding (DSO)** by client and portfolio.
  - Collection effectiveness index, average days to pay, etc.
- Provide **dashboard endpoints** consumed by reporting/BI layers.

### 6.6 API & Event Hooks

- Expose structured AR events for downstream systems:
  - `InvoiceIssued`, `PaymentRecorded`, `CreditNoteCreated`.
- Use them for:
  - Integrating with CRM/collection tools.
  - Triggering notifications or task creation when invoices age past thresholds.

---

## 7. Summary

The Accounts Receivable module in LFS provides a **full AR lifecycle**:

- Automated and manual invoice creation
- Controlled issuance with proper GL postings
- Flexible payments and credit notes
- Robust client-level reporting (Statement of Account, Aging)

The enhancement ideas above are designed to preserve these strengths while adding richer collections workflows, tax/multi-currency handling, and more powerful analytics as your usage and transaction volume grow.

