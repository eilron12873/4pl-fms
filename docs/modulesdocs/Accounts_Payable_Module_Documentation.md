# Accounts Payable (AP) Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Accounts Payable (AP)** module manages **vendor bills, payment vouchers, checks, and AP reporting** for LFS.  
It is tightly integrated with:

- **Core Accounting** (journal engine, GL, vendor invoice accruals)
- **Procurement** (Purchase Orders) for **PO-linked bills** (see `Procurement_Module_Documentation.md` for full Procurement design)
- **Treasury** (bank accounts, cash disbursement via checks)

The AP module supports both **automated accrual-driven bills** (from vendor-invoice-approved events) and **manual AP entry** (bill creation/editing), while enforcing:

- Clear separation between **draft** and **issued** bills
- Proper AP journal postings (DR Expense, CR AP)
- Accurate vendor balances, AP aging, and payment audit trail

This document covers:

- **Module structure and tech stack**
- **Key models, services, and controllers**
- **End-to-end workflows (automated + manual)**
- **Voucher & check processing**
- **Navigation menus and their behavior**
- **Enhancement recommendations**

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/AccountsPayable`
- **Layers**:
  - `Domain`: AP root (`AccountsPayable`) and rules.
  - `Application`: `BillService`, `ApReportingService`, `AmountToWords`, `AccountsPayableOverview`, event listeners.
  - `Infrastructure`: Eloquent models (`Vendor`, `ApBill`, `ApBillLine`, `ApBillAdjustment`, `ApBillPayment`, `ApPayment`, `ApVoucher`, `ApCheck`) and repository.
  - `UI`: controllers + Blade views (vendors, bills, payments, vouchers, checks, AP index, statement, aging).
  - `API`: AP-related entrypoints defined in `api.php`.
- **Service provider**: `AccountsPayableServiceProvider` wires module routes, views, and bindings.

Database objects (via AP migrations):

- `vendors` – vendor master data.
- `ap_bills` – vendor bill headers.
- `ap_bill_lines` – bill line items, optionally linked to journals.
- `ap_bill_adjustments` – vendor credit notes / bill adjustments.
- `ap_payments` – AP payment headers.
- `ap_bill_payments` – allocations from payments to bills.
- `ap_vouchers` – payment vouchers linked to payments.
- `ap_checks` – checks for check-based payments.

---

## 3. Key Components

### 3.1 Eloquent Models (Infrastructure)

- `Vendor`
  - Fields: `code`, `name`, `currency`, `payment_terms_days`, `is_active`, `notes`.
  - Used wherever vendor selection is required (bills, payments, reports).

- `ApBill`
  - Fields: `vendor_id`, `purchase_order_id`, `bill_number`, `bill_date`, `due_date`, `status`, `subtotal`, `tax_amount`, `total`, `amount_allocated`, `currency`, `notes`, `journal_id`.
  - Casts: dates and monetary fields.
  - Relations:
    - `vendor` → `Vendor`
    - `lines` → `ApBillLine`
    - `adjustments` → `ApBillAdjustment`
    - `billPayments` → `ApBillPayment`
    - `purchaseOrder` → Procurement’s `PurchaseOrder` (see `Procurement_Module_Documentation.md` for the Procurement-side model)
  - Helpers:
    - `balance_due` accessor (`total - amount_allocated`).
    - `isIssued()` helper (status in `issued`, `partially_paid`, `paid`).

- `ApBillLine`
  - Individual bill lines with description, quantity, unit price, amount.
  - May be linked to:
    - A journal (`journal_id`) from Core Accounting.
    - A vendor (`vendor_id`) and source metadata.

- `ApBillAdjustment`
  - Represents adjustments/credit notes applied to a bill.
  - Stores type, amount, and reason for reconciliation and audit.

- `ApPayment`
  - Payment header: `vendor_id`, `payment_date`, `amount`, `currency`, `reference`, `notes`, `payment_method` (`ach`/`check`), `bank_account_id`.

- `ApBillPayment`
  - Allocation line linking payments to bills (`payment_id`, `bill_id`, `amount`).
  - Updates `amount_allocated` and bill status.

- `ApVoucher`
  - Payment voucher for `ApPayment`:
    - `voucher_number`, `voucher_date`, `payment_id`.
  - Used for printing/filing payment vouchers.

- `ApCheck`
  - Check record linked to a payment:
    - Fields: `check_number`, `payment_id`, `bank_account_id`, `check_date`, `amount`, `payee`, `status`.
  - Status constants:
    - `STATUS_PRINTED`, `STATUS_VOID`.

### 3.2 Application Services

#### BillService

Central AP service for **bills, payments, vouchers, and checks**.

- **Event-driven bill line creation**
  - `createBillLineFromJournal(array $context): ApBillLine`
    - Called after a **vendor-invoice-approved** journal is posted in Core Accounting.
    - Finds or creates a **draft bill** for the vendor in the current month.
    - Creates an `ApBillLine` linked to the journal (`journal_id`, `source_type`, `source_reference`).
    - Recalculates bill totals.

- **Manual AP Entry**
  - `createManualBill(array $input): ApBill`
    - Used by the AP Entry UI to create a **draft bill** for a vendor.
    - Supports optional `purchase_order_id` to link a bill to a procurement P.O. and prefill lines.
    - Validates at least one line exists.
  - `updateDraftBill(ApBill $bill, array $input): ApBill`
    - Allows editing header and lines while status is `draft`.
    - Replaces all lines and recalculates totals.

- **Issuing Bills**
  - `issueBill(ApBill $bill, array $accountCodes = []): void`
    - No-op if already issued.
    - Posts AP journal via `JournalService::post()`:
      - Defaults: `expense` = `5200`, `payable` = `2100` (overridable via `$accountCodes`).
      - Journal description and number (`AP-BILL-{bill_number}`).
    - Stores `journal_id` and updates `status` to `issued`.

- **Payments, Vouchers, Checks**
  - `recordPayment(array $input): ApPayment`
    - Creates an `ApPayment` with method (`ach`/`check`) and optional `bank_account_id`.
    - Creates `ApBillPayment` allocations:
      - Increments `amount_allocated` on each bill.
      - Updates bill `status` to `paid` or `partially_paid` based on `total` vs `amount_allocated`.
    - Creates a `ApVoucher` for the payment with a generated voucher number.
    - If `payment_method === 'check'`:
      - Creates an `ApCheck` with:
        - `check_number` from sequence, `amount`, `check_date`, `payee` (vendor name), `status = STATUS_PRINTED`.

- **Credit Notes**
  - `createCreditNote(ApBill $bill, float $amount, string $reason = '')`
    - Writes an `ApBillAdjustment` row and adjusts bill allocations/total as needed.
    - Accessed via the bill detail UI; amount is limited to bill `balance_due`.

#### ApReportingService

Provides AP **statement** and **aging** logic for vendors.

- `statementOfAccount(int $vendorId, ?string $fromDate, ?string $toDate): array`
  - Returns:
    - `vendor` (Vendor)
    - `bills` with totals and allocations
    - `payments`
    - `balance` (outstanding)

- `agingReport(?string $asOfDate): Collection`
  - Similar to AR aging but grouped by **vendor**:
    - Buckets: `current`, `days_30`, `days_60`, `days_90`, `over_90`, `total`.

#### AmountToWords

- `AmountToWords::forCheck(float $amount, string $fractionDenom = '100'): string`
  - Converts a numeric amount to English words for checks (e.g. “One thousand and 00/100”).
  - Used in **check print** view.

### 3.3 Controllers & Routes (UI Layer)

Routes are defined in `app/Modules/AccountsPayable/routes.php` (prefix `accounts-payable`, permissions `accounts-payable.view` / `accounts-payable.manage`).

Key groups:

- **Dashboard**
  - `GET /` → `index()` (AP home dashboard cards).

- **Vendors**
  - `GET /vendors` → `vendors()` – vendor list.
  - `GET /vendors/create` → `vendorCreate()` – add vendor form.
  - `POST /vendors` → `vendorStore()` – create vendor.

- **Bills**
  - `GET /bills` → `bills()` – list, filterable by vendor and status.
  - `GET /bills/create` → `billCreate()` – manual AP entry; supports optional `purchase_order_id`.
  - `POST /bills` → `billStore()` – create draft bill.
  - `GET /bills/{id}` → `billShow()` – bill detail.
  - `GET /bills/{id}/edit` → `billEdit()` – edit draft bills only.
  - `PUT /bills/{id}` → `billUpdate()` – update draft bill.
  - `POST /bills/{id}/issue` → `issueBill()` – issue bill and post AP journal.
  - `POST /bills/{id}/credit-note` → `creditNoteStore()` – create bill credit note/adjustment.

- **Reporting**
  - `GET /statement` → `statement()` – AP Statement of Account by vendor/date.
  - `GET /aging` → `aging()` – AP Aging.

- **Payments**
  - `GET /payments` → `payments()` – payment list with vendor filter.
  - `GET /payments/create` → `paymentCreate()` – record payment form (ACH or check).
  - `POST /payments` → `paymentStore()` – create payment, allocations, voucher, and check (if applicable).

- **Vouchers**
  - `GET /vouchers` → `vouchers()` – payment voucher list.
  - `GET /vouchers/{id}` → `voucherShow()` – voucher detail/print view.

- **Checks**
  - `GET /checks` → `checks()` – check register (filterable by bank account, date).
  - `GET /checks/{id}` → `checkShow()` – check detail/print view with amount in words.
  - `POST /checks/{id}/void` → `voidCheck()` – mark printed check as void.

---

## 4. Navigation Menus & Screens

### 4.1 AP Home Dashboard

Path: `Accounts Payable → Home` (`/accounts-payable`).

Cards:

- **Vendors**
  - Navigates to `/accounts-payable/vendors`.
  - Vendor master list and entry point for vendor creation.
- **Vendor Bills**
  - Navigates to `/accounts-payable/bills`.
  - Used for reviewing and creating vendor bills.
- **Statement of Account**
  - Navigates to `/accounts-payable/statement`.
  - Vendor-wise AP statement.
- **AP Aging**
  - Navigates to `/accounts-payable/aging`.
  - Aging of outstanding AP balances.
- **Payments**
  - Navigates to `/accounts-payable/payments`.
  - Payment register and collection of payment entries.

Each feature screen usually has a **“Back to AP”** link in the header to return to this dashboard.

### 4.2 Vendors Menu

- **Vendor list**
  - Route: `GET /accounts-payable/vendors`.
  - Shows vendors with:
    - Code, name, currency, payment terms (days), status (Active/Inactive).
  - Pagination for large vendor masters.
  - For `accounts-payable.manage` users:
    - **Add vendor** button → `GET /accounts-payable/vendors/create`.

- **Add vendor form**
  - Fields: code, name, currency, payment terms days, notes.
  - On submit:
    - `POST /accounts-payable/vendors` validates uniqueness of code and creates a vendor.

### 4.3 Vendor Bills Menu

- **Bill list**
  - Route: `GET /accounts-payable/bills`.
  - Columns: bill number, vendor, bill date, total, status, actions.
  - Filters:
    - Vendor (dropdown).
    - Status (`draft`, `issued`, `partially_paid`, `paid`).
  - Actions:
    - **View** → opens bill detail.
  - Create:
    - For manage users, **Create bill** button → `GET /accounts-payable/bills/create`.

- **Create bill form**
  - Route: `GET /accounts-payable/bills/create`.
  - Fields:
    - Vendor, bill/due dates, currency, notes.
    - Optional hidden `purchase_order_id` when coming from Procurement’s **Create bill from P.O.**.
    - Lines: description + amount, with Add/Remove line behavior.
  - On submit:
    - `POST /accounts-payable/bills` → `billStore()` → `BillService::createManualBill()`.
    - Creates a **draft bill**; redirects to bill detail.

- **Bill detail view**
  - Route: `GET /accounts-payable/bills/{id}`.
  - Shows:
    - Vendor, bill/due dates, status, total, balance, linked P.O. (if any).
    - Lines table with description, quantity, unit price, amount, journal links.
    - Adjustments table for credit notes.
    - If issued and balance due > 0, a vendor credit note section.
  - Actions:
    - **Edit bill** (only for `draft` + manage users).
    - **Issue bill** – posts AP journal and sets `status = issued`.
    - **Create credit note** – limited by `balance_due`.

### 4.4 AP Reporting Menus

#### Statement of Account (By Vendor)

- Route: `GET /accounts-payable/statement`.
- Filter:
  - Required vendor selector.
  - Optional from/to dates.
  - **View** button loads statement.
- View:
  - Vendor name/code, outstanding balance.
  - Bills table: bill number (link to bill), date, total, balance, status.
  - Payments table: payment date, amount, currency, reference.
- Use:
  - Vendor reconciliation, confirming what is owed and what has been paid.

#### AP Aging

- Route: `GET /accounts-payable/aging`.
- Filter:
  - `As of date` (defaults to today); **Apply** recalculates buckets.
- View:
  - One row per vendor with:
    - Vendor code/name.
    - Buckets: current, 1–30, 31–60, 61–90, over 90, total.
- Use:
  - High-level AP exposure by vendor and age to manage payment priorities and risk.

### 4.5 Payments Menu

- **Payment list**
  - Route: `GET /accounts-payable/payments`.
  - Shows: payment date, vendor, amount, reference.
  - Filter by vendor.
  - Record payment:
    - **Record payment** button → `GET /accounts-payable/payments/create`.

- **Record payment form**
  - Route: `GET /accounts-payable/payments/create`.
  - Fields:
    - Vendor, payment date, amount, currency, reference, notes.
    - Payment method (ACH or check).
    - Bank account (if relevant).
    - Allocations table to assign payment amounts to specific bills.
  - On submit:
    - `POST /accounts-payable/payments` → `paymentStore()` → `BillService::recordPayment()`.
    - Creates an `ApPayment`, related `ApBillPayment` allocations, an `ApVoucher`, and (for checks) an `ApCheck`.

### 4.6 Vouchers Menu

- **Voucher list**
  - Route: `GET /accounts-payable/vouchers`.
  - Filters:
    - From/To date for `voucher_date`.
  - Columns:
    - Voucher number, date, vendor, amount, **View/Print** action.

- **Voucher detail / print**
  - Route: `GET /accounts-payable/vouchers/{id}`.
  - Shows:
    - Voucher header (number, date, vendor).
    - Linked payment and allocated bills.
  - Used for:
    - Printable payment voucher (signatures, filing, and approval trail).

### 4.7 Checks Menu (Check Register)

- **Check register**
  - Route: `GET /accounts-payable/checks`.
  - Filters:
    - Bank account.
    - From/To date.
  - Columns:
    - Check number, date, payee, amount, bank, status (`printed`/`void`), **View/Print** link.

- **Check detail / print**
  - Route: `GET /accounts-payable/checks/{id}`.
  - Shows:
    - Check header: number, date, payee, amount (numeric and words), bank account, status.
  - Actions:
    - If status ≠ `void` and user has `accounts-payable.manage`:
      - **Void check** button (`POST /accounts-payable/checks/{id}/void`) to mark as void.
  - Use:
    - Print physical checks (with amount in words).
    - Track which checks are outstanding or voided.

---

## 5. End-to-End Workflows

### 5.1 Automated Bills from Vendor Invoice Approval

1. **Vendor invoice is approved** in an upstream system.
2. A **financial event** (e.g. `vendor_invoice_approved`) is posted to Core Accounting.
3. Core Accounting posts an accrual/reversal + AP posting journal.
4. AP listener calls `BillService::createBillLineFromJournal()` with vendor and journal details.
5. The service:
   - Finds/creates a **draft bill** for that vendor in the current period.
   - Adds a line linked to the journal.
   - Recalculates totals.
6. Finance reviews and **issues the bill** when ready, confirming AP journal alignment and moving the bill to `issued`.

### 5.2 Manual AP Entry (Back-Office Bills)

1. User navigates to `Accounts Payable → Vendor Bills → Create`.
2. They enter:
   - Vendor, dates, currency, notes, and manual lines (description + amount).
   - Optionally arrive via Procurement’s **Create bill from P.O.**, which pre-fills vendor, currency, notes, and lines.
3. On save:
   - `billStore()` validates and calls `createManualBill()`.
   - A **draft bill** is created.
4. While draft:
   - Edit via `billEdit()` / `billUpdate()`; status prevents editing once issued.
5. When final:
   - **Issue bill** posts AP journal and sets `status = issued`.

### 5.3 Payments, Vouchers, and Checks

1. AP team navigates to **Payments → Record payment**.
2. They:
   - Select vendor, date, amount, currency, payment method, bank account, and allocations.
3. `recordPayment()`:
   - Creates `ApPayment`.
   - Allocates to bills via `ApBillPayment`, updating `amount_allocated` and bill status.
   - Creates an `ApVoucher` as the document of record for the payment.
   - If method is `check`, creates `ApCheck` with `STATUS_PRINTED`.
4. Users can:
   - View **vouchers** and print them for approval/filing.
   - View **check register**, print individual checks, and **void checks** when necessary.

### 5.4 AP Reporting Workflows

- **Statement of Account (Vendor)**
  - Choose vendor and optional date range.
  - See all bills and payments for the vendor with outstanding balance.

- **AP Aging**
  - Select an as-of date.
  - See vendors bucketed by overdue age, supporting cash planning and risk management.

---

## 6. Design Decisions & Guarantees

- **Draft vs. Issued Bills**
  - Draft bills are fully editable.
  - Issued bills are locked; subsequent changes occur through credit notes or additional bills/payments.

- **Integration with Core Accounting**
  - AP never writes directly to GL tables; all postings go through `JournalService::post()`.
  - Ensures double-entry, period locks, and audit trail.

- **Procurement Integration**
  - Optional `purchase_order_id` on bills enables basic 3-way match alignment:
    - P.O. → bill → payment, with visible linkage in AP and Procurement UIs (Procurement flows are detailed in `Procurement_Module_Documentation.md`).

- **Voucher & Check Traceability**
  - Every payment has a voucher; every check is linked to a payment and bank account.
  - Check statuses (`printed`/`void`) are explicit for audit.

---

## 7. Recommended Enhancements

These are **optional improvements** to enhance robustness, usability, and compliance of the AP module.

### 7.1 Vendor Master Enhancements

- Add:
  - **Vendor categories** (transport, customs, warehouse, general).
  - **Tax IDs**, bank details, and preferred payment methods.
- Enable **bulk import** and update with audit history.

### 7.2 Approval Workflow for Bills & Payments

- Introduce approval steps for:
  - High-value bills (e.g. bill status: `draft` → `pending_approval` → `approved` → `issued`).
  - Payments (e.g. voucher approval before release).
- Use role-based rules (e.g. based on amount thresholds, department).

### 7.3 Stronger PO–AP Matching

- Expand PO linkage by:
  - Matching per line (quantity/amount) and showing variance.
  - Reporting on **exception cases** (billed > ordered, missing P.O., etc.).

### 7.4 Enhanced Check Management

- Add optional:
  - **Check printing batches** (run checks for a group of payments in one go).
  - **Reprint tracking** and reason codes.
  - Integration with bank reconciliation module for cleared vs. outstanding checks.

### 7.5 AP Analytics & KPIs

- Extend `ApReportingService` to compute:
  - **Days Payable Outstanding (DPO)** and average days to pay by vendor.
  - Vendor concentration metrics, early-payment discounts usage, etc.
- Provide data feeds for BI dashboards.

### 7.6 Compliance & Audit Features

- Optional:
  - Require attachment references (e.g. scanned vendor invoice, P.O., GRN IDs).
  - Maintain immutable logs of changes to vendor terms and AP configurations.

---

## 8. Summary

The Accounts Payable module in LFS provides a complete **AP lifecycle**:

- Automated and manual bill creation
- Controlled issuing with proper GL postings
- Structured payment, voucher, and check processing
- Comprehensive vendor-level reporting (Statement of Account, Aging)

The enhancement ideas above aim to strengthen approvals, procurement alignment, check handling, and AP analytics while preserving the core guarantees of accuracy, auditability, and integration with Core Accounting and Procurement (see `Procurement_Module_Documentation.md` for the dedicated Procurement spec).

