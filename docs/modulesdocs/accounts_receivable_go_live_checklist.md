# Accounts Receivable Go-Live Checklist (v1.0)

Use this 10-item checklist to move AR from **technically ready** to **formally live in production**.

---

1. **Core Accounting prerequisites confirmed**
   - 6-digit COA seeded and clean (no 4-digit leftovers).
   - Posting rules active for `client-invoice-issued`, `client-payment-received`, `client-credit-note`.
   - Period controls (open/close, reopen) verified in Core Accounting UI.

2. **AR permissions and menus verified**
   - Roles assigned `accounts-receivable.view` and `accounts-receivable.manage` as appropriate.
   - AR menu entries visible and correct: Dashboard, Invoices, Statement of Account, AR Aging, Payments.

3. **End-to-end happy-path test (manual invoice)**
   - Create draft invoice → issue invoice → record payment.
   - Confirm:
     - AR invoice status transitions (`draft` → `issued` → `paid`).
     - Journals created in Core Accounting with correct accounts and amounts.
     - AR Aging and Statement of Account reflect the transaction correctly.

4. **End-to-end automated billing test (JournalPosted → AR)**
   - Trigger at least one real or seeded billable event (e.g. `shipment-delivered`).
   - Verify:
     - Journal posted via rules engine.
     - `RecordInvoiceLineFromJournal` created/updated the client’s draft invoice.
     - Finance can review and issue that invoice.

5. **Data migration / opening balances (if applicable)**
   - Decide on approach:
     - Migrate open AR invoices from legacy system, or
     - Start “day-zero” with no historical AR in LFS.
   - If migrating:
     - Load legacy open items into `ar_invoices` (or via import UI).
     - Reconcile AR Aging vs legacy AR trial balance and sign off variances.

6. **Reconciliation with Core Accounting**
   - For a chosen pilot window (e.g. 1 month):
     - Compare AR Aging “Total” vs Core Accounting AR control account balance (e.g. 121100).
     - Compare client-level Statement of Account totals vs GL per client (if dimensioned).
   - Document and resolve any differences before go-live.

7. **Error handling and user messaging**
   - Confirm AR UI surfaces friendly messages for common Core Accounting errors:
     - `PERIOD_LOCKED` (posting to closed period).
     - `RULE_NOT_FOUND` (misconfigured posting rule).
   - Train finance users on how to respond (e.g. open correct period, request rule configuration).

8. **Monitoring and logs**
   - Ensure access to:
     - Core Accounting `IntegrationLog` (or equivalent) for financial event/journal errors.
     - AR-related Laravel logs for invoice/payment failures.
   - Define who reviews these logs daily during hypercare.

9. **Operational runbook**
   - Document and share:
     - Daily tasks: issuing invoices, recording payments, generating SOA and Aging.
     - Month-end tasks: AR Aging snapshots, reconciliation steps, close/reopen rules.
     - Correction flows: use of credit notes and additional invoices (no direct edits to issued invoices).

10. **Pilot and formal sign-off**
    - Run AR for at least one pilot client or business unit for a defined period (e.g. 1–2 cycles).
    - Capture:
      - Pilot checklist (which flows were exercised).
      - Final reconciliation results (Aging vs GL).
    - Obtain written sign-off from:
      - Finance owner (for balances and flows).
      - Engineering owner (for stability and observability).

