# Approval Workflows Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Approval Workflows** module (conceptually part of **Governance, Security & Observability** under LFS Administration) is intended to orchestrate **multi-step approvals** for key financial actions, including:

- Journal approval.
- Invoice approval.
- Vendor bill approval.
- Allocation approval.
- Credit note approval.

In the current codebase, **navigation entries exist but dedicated workflow screens are not yet implemented**. Approval responsibilities are enforced primarily via:

- Core Accounting period locks.
- Role & permission management.
- Audit logs.

This document describes the **design intent**, current building blocks, and recommendations for a full Approval Workflows implementation.

---

## 2. Tech Stack & Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **UI Location**: `app/Modules/LFSAdministration` (Governance, Security & Observability).
- **Navigation configuration**: `config/navigation.php`
  - `Approval Workflows` group (label, icon, order, permission).
  - Child entries (currently routed to `lfs-administration.index` as placeholders):
    - Journal Approval (`nav_key: workflow_journal`).
    - Invoice Approval (`workflow_invoice`).
    - Vendor Bill Approval (`workflow_vendor_bill`).
    - Allocation Approval (`workflow_allocation`).
    - Credit Note Approval (`workflow_credit_note`).
- **Existing governance infrastructure**:
  - `LFSAdministrationController`:
    - `auditLogs()` – user/activity and financial posting logs.
    - `roles()` / `roleEdit()` / `roleUpdate()` – role & permission management.
    - `integrationEvents()` / `syncLogs()` – integration monitoring.
  - `Activity` model (via `spatie/laravel-activitylog`).
  - `Role`, `Permission` models (via `spatie/laravel-permission`).

These components, combined with existing financial modules, provide the foundation on which explicit approval workflows can be built.

---

## 3. Target Approval Areas (Conceptual)

### 3.1 Journal Approval (Core Accounting / GL)

Objective:

- Require review and approval of **manual or sensitive journals** before posting to GL.

Intended behavior:

- Journals created in a **draft** state (status `draft` or `pending_approval`).
- Approvers (with dedicated permission) can:
  - Review journal lines, postings, and source references.
  - Approve (post) or reject/cancel the journal.
- Once approved:
  - `JournalService::post()` is invoked and the journal becomes immutable.

### 3.2 Invoice Approval (Accounts Receivable)

Objective:

- Introduce an optional approval step between **draft** and **issued** invoices, especially for:
  - Large invoice amounts.
  - Exceptional discounting or credit conditions.

Intended behavior:

- Draft invoices either:
  - Flow directly to “Issue” (today’s behavior) where policy allows; or
  - Enter a **pending approval queue** before issuance.
- Approvers can:
  - Review invoice header, lines, and related journals.
  - Approve → `issueInvoice()` is triggered.
  - Reject → invoice remains draft with comments for correction.

### 3.3 Vendor Bill Approval (Accounts Payable)

Objective:

- Control and document approval of **vendor bills** before they are issued / posted to AP.

Intended behavior:

- Draft bills enter a **Vendor Bill Approval** queue.
- Approvers validate:
  - Vendor, dates, amounts, linked P.O., and cost coding.
  - 3-way match (P.O. / receipt / bill) where applicable.
- Approved bills:
  - Are issued via `issueBill()` (posting AP journal).

### 3.4 Allocation Approval (Costing & Profitability)

Objective:

- Review and approve **manual cost allocation adjustments** (e.g. overrides to the Allocation Engine).

Intended behavior:

- Proposed allocations:
  - Are created as pending allocation journals or adjustment entries.
- Approvers:
  - Check allocation rationale and impact.
  - Approve (post allocation) or reject (return to requester).

### 3.5 Credit Note Approval (AR/AP)

Objective:

- Require approval for **credit notes** that reduce revenue or AP liability.

Intended behavior:

- Credit note requests (AR and AP) are:
  - Logged with amount, reason, and reference document.
- Approvers:
  - Validate reason and amount.
  - Approve to create credit note and related journals.

---

## 4. Current Navigation & User Experience

Navigation entries for Approval Workflows are defined in `config/navigation.php`:

- Group:
  - **Label**: `Approval Workflows`
  - **Icon**: `fas fa-check-circle`
  - **Permission**: `lfs-administration.view`
- Children:
  - **Journal Approval**
  - **Invoice Approval**
  - **Vendor Bill Approval**
  - **Allocation Approval**
  - **Credit Note Approval**

At present:

- These links route back to the **LFS Administration dashboard** (`lfs-administration.index`).
- Actual workflow-specific listing/approval screens are a **planned enhancement**.

Users can already:

- Navigate to `Governance, Security & Observability` to:
  - View **Audit Logs** (all actions, including financial postings).
  - Manage **roles & permissions**.
- Use existing module screens (AR/AP/Core Accounting) with:
  - Implicit approval via **roles, permissions, and period locking**.

---

## 5. Recommended Workflow Implementation (Future Design)

Although not implemented yet, the following design is recommended to fully realize the Approval Workflows module.

### 5.1 Shared Workflow Model & States

Introduce a generic **workflow state model**:

- Could be per-entity (`journal_approvals`, `invoice_approvals`, etc.) or a polymorphic `approvals` table with:
  - `approvable_type`, `approvable_id`.
  - `status` (`pending`, `approved`, `rejected`, `cancelled`).
  - `requested_by`, `approved_by`, `approved_at`.
  - `reason` / `comments`.

States:

- **Pending** – waiting on approver.
- **Approved** – entity can proceed (e.g. post or issue).
- **Rejected** – entity is blocked; requester must revise or cancel.

### 5.2 Approval Queues per Area

Create dedicated screens for each approval type:

- **Journal Approval Queue**
  - List journals in `draft` or `pending_approval` status.
  - Show key data: date, number, description, total debit/credit, requestor.
  - Actions:
    - View (drill-down).
    - Approve (post journal).
    - Reject (with comments).

- **Invoice Approval Queue**
  - Filter by client, amount, date, and status.
  - Show: invoice number, client, total, requested changes, reason.

- **Vendor Bill Approval Queue**
  - Similar to invoice approval but for AP bills.
  - Includes P.O. linkage indicator and variance, where available.

- **Allocation & Credit Note Queues**
  - Show pending manual allocations or credit note requests with:
    - Impact on P&L or balances.
    - Reason and supporting information.

### 5.3 Threshold & Rule-Based Approvals

Leverage the **System Settings** module (see `System_Settings_Module_Documentation.md`) together with **Role & Permission Management** to define:

- **Approval thresholds** (configured under **Financial Controls** in System Settings):
  - E.g. invoices over X require manager approval; over Y require CFO approval.
  - Separate thresholds for:
    - Journals (Core Accounting).
    - Invoices (AR).
    - Vendor bills (AP).
    - Credit notes and allocation overrides where applicable.
- **Workflow routing rules**:
  - Stored in System Settings (or a related configuration model) and evaluated by the workflow engine.
  - Rules by department, cost center, service line, client, or vendor.

### 5.4 Audit & Governance Integration

- Every approval/rejection action:
  - Should be logged to `Activity` with:
    - Approver identity.
    - Before/after state.
    - Reason and comments.
- Combined with **Integration Center** and **Sync Logs**, this provides:
  - End-to-end traceability from external events to journals and approvals.

---

## 6. How the Module Was Created (Current State)

In the current iteration:

- The module exists primarily as:
  - **Navigation entries** in `config/navigation.php`.
  - **Blueprint documentation** in `docs/LFS_UI_Navigation_Blueprint.md` (section “1️⃣1️⃣ Approval Workflows”).
  - **Shared governance tools**:
    - Audit Logs.
    - Role & Permission Management.
    - Integration Center (Financial Events Monitor and Sync Logs).
- Approvals are enforced implicitly via:
  - Role-based access (e.g. only certain roles can post journals, issue invoices/bills).
  - Period locking in Core Accounting.
  - Manual process and audit oversight.

The next step is to evolve from **implicit**, policy-based approvals into **explicit, UI-driven workflow queues** as outlined above.

---

## 7. Recommended Enhancements (Summary)

These enhancements would transform Approval Workflows from a navigation placeholder into a fully functional module:

- Implement **per-entity approval queues** for journals, invoices, vendor bills, allocations, and credit notes.
- Add a shared **approval model** with states, approver metadata, and comments.
- Configure **threshold-based** and **rule-based** approvals via the **System Settings** module (Financial Controls), ensuring a single, auditable source of truth for approval policies that other modules can read at runtime.
- Enhance Audit Logs to show **approval actions** as first-class events.
- Integrate with Integration Center for:
  - Approvals related to high-risk or externally triggered events.
- Provide a **dashboard** summarizing:
  - Pending approvals by type and age.
  - SLA metrics for approvals (e.g. average time to approve).

---

## 8. Summary

The Approval Workflows module is currently a **conceptual layer** backed by roles, permissions, and audit, with clear navigation placeholders for future implementation.

By adding explicit approval queues, shared workflow state, and rule-based routing, LFS can provide a robust, auditable approval system covering journals, invoices, vendor bills, allocations, and credit notes, fully aligned with the governance and integration capabilities already present in the platform.

