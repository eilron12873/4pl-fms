# LFS Gap Assessment & Implementation Plan

This document compares the requested feature set against the current 4pl-fms/LFS codebase, describes gaps, and outlines an implementation plan to close them.

**Implementation status (as implemented):** Phases A–E have been implemented: P&L per Revenue, Manual AR Entry, Manual AP Entry, Voucher & Check, and P.O./P.R. Procurement (basic CRUD). See below for details.

**Requested features:**

1. P&L per Revenue  
2. Trial Balance  
3. AR Entry  
4. AP Entry  
5. Invoicing  
6. Voucher (AP and Check)  
7. AR Aging  
8. AP Aging  
9. Cash Flow  
10. P.O./P.R. Procurement  

---

## 1. Feature Coverage Summary

| # | Feature | Status | Notes |
|---|--------|--------|------|
| 1 | P&L per Revenue | **Partial** | Income Statement and Management P&L by dimension exist; no dedicated "P&L per Revenue" (e.g. by revenue type/segment). |
| 2 | Trial Balance | **Yes** | `general-ledger.trial-balance`, `ReportingService::trialBalance()`. |
| 3 | AR Entry | **Partial** | Invoices (list/show/issue/credit note/payments); no **manual** "Create AR invoice" / AR entry form. |
| 4 | AP Entry | **Partial** | Bills (list/show/issue/credit note/payments); no **manual** "Create AP bill" / AP entry form. |
| 5 | Invoicing | **Yes** | AR Invoices (BillingEngine, WMS feed, issue, credit note, payments). |
| 6 | Voucher (AP and Check) | **No** | AP "Record payment" exists; no voucher document (number, print) or check (printing, register). |
| 7 | AR Aging | **Yes** | `accounts-receivable.aging`, `ArReportingService::agingReport()`. |
| 8 | AP Aging | **Yes** | `accounts-payable.aging`, AP aging report. |
| 9 | Cash Flow | **Yes** | GL Cash Flow (indirect) + Cash flow analysis (GL + Treasury). |
| 10 | P.O./P.R. Procurement | **No** | No Purchase Order or Purchase Request module. |

---

## 2. Gap Description

### 2.1 P&L per Revenue (Partial Gap)

- **Current:** Income Statement (`general-ledger.income-statement`) has sections: Revenue, Cost of Revenue, Operating Expenses, Other Income/Expense. Management P&L by dimension (client, warehouse, project) exists.
- **Gap:** No report explicitly titled "P&L per Revenue" that breaks down P&L by **revenue type/segment** (e.g. by account range 41–44, or by service line / revenue category). Config `gl_statements.income_statement` has a single "revenue" block (prefixes 41–44), not per-revenue-type.

### 2.2 AR Entry (Partial Gap)

- **Current:** Invoices are created via (1) BillingEngine/contracts, (2) WMS billing feed, (3) `JournalPosted` → `RecordInvoiceLineFromJournal`. There are routes for list, show, issue, credit note, and payment (create/store).
- **Gap:** No UI to **manually create an AR invoice** (header + lines, client, date, amounts) without going through events or billing engine. "AR Entry" as a manual transaction screen is missing.

### 2.3 AP Entry (Partial Gap)

- **Current:** Bills are created from `VendorInvoiceApproved` → `RecordBillLineFromJournal`. Routes: list, show, issue, credit note, payments (create/store).
- **Gap:** No UI to **manually create an AP bill** (vendor, date, lines, amounts). "AP Entry" as a manual bill entry is missing.

### 2.4 Voucher (AP and Check) (Full Gap)

- **Current:** AP payments are recorded (`ApPayment`, allocations to bills via `ApBillPayment`). No voucher or check concepts.
- **Gap:**
  - **Voucher:** No "payment voucher" entity (voucher number, link to payment/bills, print voucher for approval/filing).
  - **Check:** No check printing (check layout, check number), no check register (list of checks issued), no bank account / check run workflow.

### 2.5 P.O./P.R. Procurement (Full Gap)

- **Current:** No procurement module; no tables or routes for purchase orders or purchase requests.
- **Gap:** No Purchase Order (PO) or Purchase Request (PR) lifecycle: create, approve, send to vendor, receive, match to AP bill. No integration point from PO/PR to AP.

---

## 3. Implementation Plan to Fill the Gaps

### Phase A: P&L per Revenue (Small)

| Step | Action |
|------|--------|
| A1 | Add a report "P&L per Revenue" (e.g. under Financial Reports): either a new report that shows Income Statement with **revenue section split** by account prefix or by configurable revenue segments (e.g. in `config/gl_statements.php`: `revenue_breakdown` with sub-prefixes 41, 42, 43, 44 or by service_line_id if stored on lines), or add a "Revenue type" dimension to GL and reuse/extend `incomeStatementByDimension` for a "revenue type" dimension. |
| A2 | Add route + view + controller method; wire from Financial Reports (or General Ledger) index. |

### Phase B: Manual AR Entry (Medium)

| Step | Action |
|------|--------|
| B1 | Add routes: `GET/POST accounts-receivable/invoices/create`, optional `GET accounts-receivable/invoices/{id}/edit` (only for draft). |
| B2 | Build "Create Invoice" form: client, invoice date, due date, currency; line items (description, amount, optional account/service type); totals. |
| B3 | Use existing `InvoiceService::createInvoiceFromBilling()` (or a new method e.g. `createManualInvoice()`) to create draft `ArInvoice` + `ArInvoiceLine` without posting; keep "Issue" for posting as today. |
| B4 | Permissions: e.g. `accounts-receivable.manage` for create; ensure validation (client exists, amounts ≥ 0, totals match). |

### Phase C: Manual AP Entry (Medium)

| Step | Action |
|------|--------|
| C1 | Add routes: `GET/POST accounts-payable/bills/create`, optional `GET accounts-payable/bills/{id}/edit` (draft only). |
| C2 | Build "Create Bill" form: vendor, bill date, due date, currency; line items (description, amount, optional account); totals. |
| C3 | Add `BillService::createManualBill()` (or equivalent) to create draft `ApBill` + `ApBillLine`; keep "Issue bill" for posting. |
| C4 | Permissions: e.g. `accounts-payable.manage`; validation analogous to AR. |

### Phase D: Voucher and Check (Medium–Large)

| Step | Area | Action |
|------|------|--------|
| D1 | Voucher | Add `ap_vouchers` table: id, voucher_number (unique), payment_id (FK to ap_payments), voucher_date, status, created_at, etc. Optionally link to one or more bills. Generate voucher number on payment (or in a "Create voucher" step). Add "Print voucher" view (PDF or print-friendly Blade) showing payment, vendor, allocations to bills. Add route/view for "Voucher" list and "View/Print voucher" from payment or from a new "Vouchers" menu. |
| D2 | Check | Add `ap_checks` table: id, check_number, payment_id, bank_account_id, check_date, amount, payee, status (printed/void), created_at. Optionally derive payee from `ApPayment`/vendor. When recording payment, add "Payment method" (e.g. check / ACH). If "Check", create `ApCheck` and assign check number (sequence per bank account). "Check register" report: list checks (date, number, payee, amount, bank, status) with filters. "Print check" view: layout with check number, date, payee, amount (words + figures), bank. Use same bank account data as Treasury if applicable. |
| D3 | Link | Link voucher to check when payment method is check (voucher references check number; or check references voucher). |

### Phase E: P.O./P.R. Procurement (Large)

| Step | Area | Action |
|------|------|--------|
| E1 | Data model | **Purchase Request (PR):** `purchase_requests` (id, pr_number, requested_by, department, request_date, status, approval_date, notes), `purchase_request_lines` (item/description, quantity, estimated_unit_cost, account_code, etc.). **Purchase Order (PO):** `purchase_orders` (id, po_number, vendor_id, pr_id nullable, order_date, expected_date, status, total, currency), `purchase_order_lines` (description, quantity, unit_price, amount, account_code, pr_line_id nullable). |
| E2 | Workflow | PR: Create → Submit → Approve (optional approval matrix). PO: Create (optionally from PR); approve; send to vendor (document/email); receive (optional receiving module); "Match to bill" (link PO/PO lines to AP bill when entering or approving vendor invoice). |
| E3 | AP integration | When creating or issuing an AP bill, optionally link to PO (bill has `purchase_order_id` or bill lines link to `purchase_order_line_id` for 3-way match). Reuse existing `ApBill` / `BillService`; extend with PO reference and optional matching rules. |
| E4 | UI | Menu: e.g. "Procurement" or "Purchasing" with PR list/create/show and PO list/create/show. Permissions: e.g. `procurement.view`, `procurement.manage` or `purchase-orders.manage`, `purchase-requests.manage`. |

---

## 4. Suggested Order of Implementation

1. **P&L per Revenue (Phase A)** – Small, high visibility.  
2. **Manual AR Entry (Phase B)** – Completes "AR Entry" and improves usability.  
3. **Manual AP Entry (Phase C)** – Completes "AP Entry" and symmetry with AR.  
4. **Voucher (AP and Check) (Phase D)** – Required for formal AP payment and check handling.  
5. **P.O./P.R. Procurement (Phase E)** – Largest; can be phased (PR first, then PO, then AP matching).  

---

## 5. References (Current Codebase)

- **Trial Balance:** `app/Modules/GeneralLedger/` — `ReportingService::trialBalance()`, route `general-ledger.trial-balance`.  
- **Income Statement / P&L:** `app/Modules/GeneralLedger/Application/ReportingService.php` (`incomeStatement`, `incomeStatementByDimension`), `config/gl_statements.php`.  
- **AR:** `app/Modules/AccountsReceivable/` — `InvoiceService`, routes under `accounts-receivable.*`.  
- **AP:** `app/Modules/AccountsPayable/` — `BillService`, routes under `accounts-payable.*`.  
- **Cash Flow:** `ReportingService::cashFlowIndirect()`, route `general-ledger.cash-flow`, Financial Reporting cash flow analysis.  
- **Aging:** `ArReportingService::agingReport()`, `ApReportingService::agingReport()`; routes `accounts-receivable.aging`, `accounts-payable.aging`.  

---

---

## 6. Implemented (Summary)

| Phase | Delivered |
|-------|-----------|
| **A** | **P&L per Revenue:** `config/gl_statements.php` `revenue_breakdown`; `ReportingService::plPerRevenue()`; route `financial-reporting.pl-per-revenue`; view `pl-per-revenue.blade.php`; nav "P&L per Revenue". |
| **B** | **Manual AR Entry:** `InvoiceService::createManualInvoice()`; routes `invoices/create`, `POST invoices`; view `invoices/create.blade.php`; "Create invoice" on invoices index. |
| **C** | **Manual AP Entry:** `BillService::createManualBill()`; routes `bills/create`, `POST bills`; view `bills/create.blade.php`; "Create bill" on bills index. |
| **D** | **Voucher & Check:** Migrations `ap_vouchers`, `ap_checks`, `payment_method`/`bank_account_id` on `ap_payments`; models `ApVoucher`, `ApCheck`; voucher/check created in `BillService::recordPayment()`; payment form has Payment method (ACH/Check) and Bank account; routes `vouchers`, `vouchers/{id}`, `checks`, `checks/{id}`; views for list and print. Nav: Vouchers, Check register. |
| **E** | **P.O./P.R. Procurement:** New module `Procurement`; tables `purchase_requests`, `purchase_request_lines`, `purchase_orders`, `purchase_order_lines`; models; routes and controller; views for P.R. and P.O. (list, create, show). Permissions `procurement.view`, `procurement.manage`. No AP matching or approval workflow in v1. |

---

*Document generated from gap assessment of the 4pl-fms/LFS project. Phases A–E implemented; update as needed for future enhancements.*
