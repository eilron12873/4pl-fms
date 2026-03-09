# Procurement Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Procurement** module manages **Purchase Requests (P.R.) and Purchase Orders (P.O.)** for LFS.  
It provides a structured way to:

- Capture internal **purchase requests** with quantities, estimated costs, and account coding.
- Convert approved requests into **Purchase Orders** issued to vendors.
- Track P.O. status through **Draft → Issued → Received**.
- Hand off received P.O.s to **Accounts Payable** for bill creation and matching.

This document describes:

- How the module was created and where it lives in the codebase.
- Tech stack and architectural structure.
- Key models, controller methods, and workflows.
- How each Procurement navigation menu operates.
- Recommended enhancements for approvals, matching, and analytics.

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/Procurement`
- **Module manifest**: `app/Modules/Procurement/module.json`
  - `name`: `Procurement`
  - `description`: `Purchase requests and purchase orders`
  - `permissions`:
    - `procurement.view`
    - `procurement.manage`
  - `nav`:
    - `label`: `Procurement`
    - `route`: `procurement.index`
    - `icon`: `fas fa-shopping-cart`
    - `order`: `35`

### 2.1 Layers

- **UI**:
  - `ProcurementController` in `UI/Controllers`.
  - Blade views in `UI/Views`:
    - `index.blade.php` (Procurement home).
    - `purchase-requests/index.blade.php`, `purchase-requests/create.blade.php`, `purchase-requests/show.blade.php`.
    - `purchase-orders/index.blade.php`, `purchase-orders/create.blade.php`, `purchase-orders/show.blade.php`.
- **Infrastructure (Eloquent models)**:
  - `PurchaseRequest`
  - `PurchaseRequestLine`
  - `PurchaseOrder`
  - `PurchaseOrderLine`
- **Service provider**:
  - `ProcurementServiceProvider` wires routes, views, and module bootstrapping.
- **Routing**:
  - `app/Modules/Procurement/routes.php`
  - All routes use:
    - Middleware: `auth`, `verified`, `permission:procurement.view` (plus `procurement.manage` for modifying actions).
    - Prefix: `/procurement`
    - Name prefix: `procurement.*`

### 2.2 Database Objects (Conceptual)

Via migrations (not listed here in full), the module uses:

- `purchase_requests`
- `purchase_request_lines`
- `purchase_orders`
- `purchase_order_lines`

---

## 3. Key Components

### 3.1 Eloquent Models (Infrastructure)

#### PurchaseRequest

- Table: `purchase_requests`
- Constants:
  - `STATUS_DRAFT = 'draft'`
  - `STATUS_SUBMITTED = 'submitted'`
  - `STATUS_APPROVED = 'approved'`
- Fillable:
  - `pr_number`, `requested_by`, `department`, `request_date`, `status`, `approval_date`, `notes`.
- Casts:
  - `request_date` → `date`
  - `approval_date` → `date`
- Relations:
  - `lines(): HasMany` → `PurchaseRequestLine`

#### PurchaseRequestLine

- Table: `purchase_request_lines`
- Fillable:
  - `purchase_request_id`, `description`, `quantity`, `estimated_unit_cost`, `account_code`.
- Casts:
  - `quantity` → `decimal:4`
  - `estimated_unit_cost` → `decimal:4`
- Relations:
  - `purchaseRequest(): BelongsTo` → `PurchaseRequest`

#### PurchaseOrder

- Table: `purchase_orders`
- Constants:
  - `STATUS_DRAFT = 'draft'`
  - `STATUS_ISSUED = 'issued'`
  - `STATUS_RECEIVED = 'received'`
- Fillable:
  - `po_number`, `vendor_id`, `purchase_request_id`, `order_date`, `expected_date`, `received_date`, `status`, `total`, `currency`.
- Casts:
  - `order_date`, `expected_date`, `received_date` → `date`
  - `total` → `decimal:2`
- Relations:
  - `vendor(): BelongsTo` → `Vendor` (from Accounts Payable).
  - `purchaseRequest(): BelongsTo` → `PurchaseRequest`.
  - `lines(): HasMany` → `PurchaseOrderLine`.

#### PurchaseOrderLine

- Table: `purchase_order_lines`
- Fillable:
  - `purchase_order_id`, `purchase_request_line_id`, `description`, `quantity`, `unit_price`, `amount`, `account_code`.
- Casts:
  - `quantity` → `decimal:4`
  - `unit_price` → `decimal:4`
  - `amount` → `decimal:2`
- Relations:
  - `purchaseOrder(): BelongsTo` → `PurchaseOrder`.
  - `purchaseRequestLine(): BelongsTo` → `PurchaseRequestLine`.

---

## 4. Controller & Routes

All routes are defined in `app/Modules/Procurement/routes.php` and implemented by `ProcurementController`.

### 4.1 Route Overview

- **Prefix**: `/procurement`
- **Name prefix**: `procurement.*`
- **Base middleware**: `auth`, `verified`, `permission:procurement.view`
- **Create / update middleware**: `permission:procurement.manage`

Key routes:

- Dashboard:
  - `GET /procurement` → `index()` → `procurement.index`
- Purchase Requests:
  - `GET /procurement/purchase-requests` → `purchaseRequests()` → `purchase-requests.index`
  - `GET /procurement/purchase-requests/create` → `purchaseRequestCreate()` → `purchase-requests.create` (manage)
  - `POST /procurement/purchase-requests` → `purchaseRequestStore()` → `purchase-requests.store` (manage)
  - `GET /procurement/purchase-requests/{id}` → `purchaseRequestShow()` → `purchase-requests.show`
  - `POST /procurement/purchase-requests/{id}/submit` → `purchaseRequestSubmit()` → `purchase-requests.submit` (manage)
  - `POST /procurement/purchase-requests/{id}/approve` → `purchaseRequestApprove()` → `purchase-requests.approve` (manage)
- Purchase Orders:
  - `GET /procurement/purchase-orders` → `purchaseOrders()` → `purchase-orders.index`
  - `GET /procurement/purchase-orders/create` → `purchaseOrderCreate()` → `purchase-orders.create` (manage)
  - `POST /procurement/purchase-orders` → `purchaseOrderStore()` → `purchase-orders.store` (manage)
  - `POST /procurement/purchase-orders/{id}/issue` → `purchaseOrderIssue()` → `purchase-orders.issue` (manage)
  - `POST /procurement/purchase-orders/{id}/receive` → `purchaseOrderReceive()` → `purchase-orders.receive` (manage)
  - `GET /procurement/purchase-orders/{id}` → `purchaseOrderShow()` → `purchase-orders.show`

### 4.2 ProcurementController Highlights

#### Dashboard – `index()`

- Renders `procurement::index`.
- Provides a simple landing page with cards linking to:
  - Purchase Requests list.
  - Purchase Orders list.

#### Purchase Requests

- `purchaseRequests(Request $request): View`
  - Queries `PurchaseRequest` with `lines_count`:
    - Orders by `request_date` descending.
    - Optional filter by `status`.
  - Paginates results and passes them to `purchase-requests.index`.
- `purchaseRequestCreate(): View`
  - Returns `purchase-requests.create` view.
  - The form allows adding multiple lines (description, quantity, estimated unit cost, account code).
- `purchaseRequestStore(Request $request): RedirectResponse`
  - Validates header and line fields (at least one line required).
  - Uses a DB transaction to:
    - Create `PurchaseRequest` with:
      - Auto-generated `pr_number` via `generatePrNumber()`.
      - `status = draft`.
    - Create `PurchaseRequestLine` rows, computing numeric values.
  - Redirects to the P.R. show page with a success flash message.
- `purchaseRequestShow(int $id): View`
  - Loads a P.R. with `lines` and renders details.
- `purchaseRequestSubmit(int $id): RedirectResponse`
  - Only allows transition from `draft` to `submitted`.
  - Validates state and updates status; redirects with success or error message.
- `purchaseRequestApprove(int $id): RedirectResponse`
  - Only allows transition from `submitted` to `approved`.
  - Sets `approval_date = now()` and updates status.

#### Purchase Orders

- `purchaseOrders(Request $request): View`
  - Queries `PurchaseOrder` with:
    - `vendor` relation.
    - `lines_count`.
    - Filters:
      - `status` (optional).
      - `vendor_id` (optional).
  - Paginates and passes:
    - `orders` and `vendors` to `purchase-orders.index`.
- `purchaseOrderCreate(Request $request): View`
  - Loads:
    - Active `Vendor` list (from Accounts Payable module).
    - `PurchaseRequest` options in `draft` or `approved` status.
  - Renders `purchase-orders.create`, enabling:
    - Standalone P.O. creation.
    - P.O. referencing a P.R. (without automatic line-copying in v1).
- `purchaseOrderStore(Request $request): RedirectResponse`
  - Validates:
    - Vendor, optional `purchase_request_id`.
    - Dates and currency.
    - At least one line with quantity, unit price, and optional account code.
  - In a DB transaction:
    - Creates `PurchaseOrder` with:
      - Auto-generated `po_number` via `generatePoNumber()`.
      - `status = draft`.
      - `currency` defaulting to vendor currency when not specified.
    - For each line:
      - Calculates `amount = quantity * unit_price`.
      - Creates `PurchaseOrderLine`.
      - Accumulates total.
    - Updates the header `total`.
  - Redirects to the P.O. show page with success message.
- `purchaseOrderShow(int $id): View`
  - Loads a P.O. with `vendor`, `lines`, and `purchaseRequest`.
  - The view includes:
    - Action buttons for issuing, marking received, and (in AP) “Create bill from P.O.”.
- `purchaseOrderIssue(int $id): RedirectResponse`
  - Only allows transition from `draft` to `issued`.
  - Enforces status check; redirects with appropriate message.
- `purchaseOrderReceive(int $id): RedirectResponse`
  - Only allows transition from `issued` to `received`.
  - Sets `received_date = now()` and updates status.

---

## 5. Workflows

### 5.1 Purchase Request Workflow (P.R.)

1. **Create P.R. (Draft)**
   - User with `procurement.manage` navigates to:
     - Procurement → Purchase Requests → “Create”.
   - Enters:
     - Requested-by, department, request date, notes.
     - One or more lines:
       - Description.
       - Quantity.
       - Estimated unit cost.
       - Optional account code.
   - System:
     - Generates a unique `PR-YYYY-#####` number.
     - Saves the P.R. with `status = draft`.
2. **Submit P.R.**
   - On the P.R. detail view:
     - “Submit P.R.” button appears when `status = draft` and user has `procurement.manage`.
   - System:
     - Changes status to `submitted`.
     - Records audit trail via standard activity logging.
3. **Approve P.R.**
   - On the same detail view:
     - “Approve P.R.” button appears when `status = submitted`.
   - System:
     - Marks status `approved`.
     - Sets `approval_date = now()`.
4. **Use P.R. in P.O. creation (optional)**
   - When creating a P.O., an approved P.R. can be referenced via `purchase_request_id` for traceability.

### 5.2 Purchase Order Workflow (P.O.)

1. **Create P.O. (Draft)**
   - User with `procurement.manage` navigates to:
     - Procurement → Purchase Orders → “Create”.
   - Selects:
     - Vendor.
     - Optional linked P.R.
     - Order date, expected date.
     - Currency (defaults from vendor if left blank).
   - Adds lines with:
     - Description.
     - Quantity.
     - Unit price.
     - Optional account code.
   - System:
     - Generates `PO-YYYY-#####`.
     - Calculates `amount = quantity * unit_price` per line.
     - Sets header `total` and `status = draft`.
2. **Issue P.O.**
   - On P.O. detail page:
     - “Issue P.O.” button is visible when `status = draft` and user has `procurement.manage`.
   - System:
     - Validates status.
     - Updates status to `issued`.
3. **Mark P.O. Received**
   - On P.O. detail page:
     - “Mark received” button appears when `status = issued`.
   - System:
     - Validates status.
     - Sets `status = received`.
     - Records `received_date = now()`.
4. **Integration with AP – Create Bill from P.O.**
   - When a P.O. is `issued` or `received` and user has `accounts-payable.manage`:
     - A “Create bill from P.O.” link appears (in the P.O. view) pointing to AP:
       - `accounts-payable.bills.create` with `purchase_order_id`.
   - In AP:
     - Vendor, currency, notes, and lines can be pre-filled to create a draft vendor bill.
     - The AP bill keeps a link back to the P.O. for basic matching and audit.

---

## 6. Navigation Menus & Screens

### 6.1 Sidebar Navigation

- The module registers itself via `module.json` as:
  - `nav.label = Procurement`
  - `nav.route = procurement.index`
  - `nav.icon = fas fa-shopping-cart`
  - `nav.order = 35`
- In the main LFS sidebar:
  - **Procurement** appears as a top-level entry.
  - Clicking it routes to `/procurement` (dashboard).
  - Visibility is controlled by:
    - `permission:procurement.view`

### 6.2 Procurement Dashboard (`/procurement`)

- View: `procurement::index`.
- Layout:
  - Uses `x-app-layout` with header **“Procurement”**.
  - Displays two main cards:
    - **Purchase requests**
      - Icon: clipboard-style.
      - Link: `route('procurement.purchase-requests.index')`.
      - Description: “Create and view purchase requests (P.R.)”.
    - **Purchase orders**
      - Icon: file/purchase-order style.
      - Link: `route('procurement.purchase-orders.index')`.
      - Description: “Create and view purchase orders (P.O.)”.
- Purpose:
  - Acts as a landing page and entry point into the P.R. and P.O. workflows.

### 6.3 Purchase Requests Menu & Screens

- **List** – `GET /procurement/purchase-requests`
  - Shows a paginated table of P.R.s with:
    - P.R. number, request date, requested-by, department, status, line count.
  - Filters:
    - Optional status filter via query string.
- **Create** – `GET /procurement/purchase-requests/create`
  - Form to capture:
    - Header: requested-by, department, request date, notes.
    - Lines: dynamic rows with description, quantity, estimated unit cost, and account code.
- **Store** – `POST /procurement/purchase-requests`
  - Validates and saves draft P.R. + lines.
- **Show** – `GET /procurement/purchase-requests/{id}`
  - Detail view including:
    - Header info and list of lines.
    - Buttons:
      - “Submit P.R.” if status is `draft` and user can manage procurement.
      - “Approve P.R.” if status is `submitted` and user can manage procurement.

### 6.4 Purchase Orders Menu & Screens

- **List** – `GET /procurement/purchase-orders`
  - Shows paginated P.O. list with:
    - P.O. number, vendor, order date, expected date, status, total, line count.
  - Filters:
    - By status.
    - By vendor.
  - Includes vendor dropdown sourced from active vendors.
- **Create** – `GET /procurement/purchase-orders/create`
  - Header fields:
    - Vendor (required).
    - Optional P.R. reference (draft or approved).
    - Order date, expected date, currency.
  - Lines:
    - Description, quantity, unit price, account code.
  - Allows creation of both standalone and P.R.-linked P.O.s.
- **Store** – `POST /procurement/purchase-orders`
  - Validates fields and lines; computes totals as described previously.
- **Show** – `GET /procurement/purchase-orders/{id}`
  - Detail view with:
    - Vendor, dates, status, currency, total.
    - Linked P.R. (if any).
    - Lines table.
    - Action buttons (depending on status and permissions):
      - “Issue P.O.” (when `draft`).
      - “Mark received” (when `issued`).
      - **“Create bill from P.O.”** (when `issued` or `received` and user can manage AP).

---

## 7. Design Decisions & Guarantees

- **Simple, explicit state machines**:
  - P.R.: `draft → submitted → approved`.
  - P.O.: `draft → issued → received`.
  - State transitions are enforced in controller methods with clear error messaging.
- **Separation of concerns**:
  - Procurement focuses on **commitment and approval to spend**.
  - AP focuses on **actual vendor bills and payments**, with a link back to P.O.s.
- **Vendor-centric integration**:
  - P.O. vendor is drawn from the central `Vendor` master in Accounts Payable.
  - Currency defaults from vendor, reducing configuration duplication.
- **Auditability**:
  - Status changes and creations go through standard Laravel flows (and can be captured via Activity log configuration).
  - P.R. and P.O. IDs and numbers provide a clear reference chain into AP and Core Accounting.

---

## 8. Recommended Enhancements

These are future improvements that can strengthen the Procurement module.

### 8.1 Deeper P.R. ↔ P.O. Integration

- Automatically:
  - Copy lines from an **approved P.R.** into a new P.O.:
    - Preserve description, quantity, and account coding.
    - Allow editing on the P.O. with clear variance tracking.
  - Show P.R. status impact when P.O. is completed (e.g. mark P.R. as “fulfilled”).

### 8.2 3-Way Matching Support

- Extend data model and UI to support:
  - Comparing:
    - P.R. (requested).
    - P.O. (ordered).
    - AP bill (invoiced).
  - Highlight variances (quantity or price) and require explicit approval for large deviations.
- Integrate flags into:
  - AP “Create bill from P.O.” flow.
  - Future Approval Workflows (e.g. require approval for mismatches over a threshold).

### 8.3 Procurement Analytics & Reporting

- Add summary screens and reports:
  - Open P.R.s and P.O.s by department, vendor, and age.
  - Spend committed vs actual bills by vendor or category.
  - Lead time from request to approval to order to receipt.
- Surface these analytics on:
  - Procurement dashboard cards.
  - Cross-module dashboards (Costing & Profitability, Core Accounting).

### 8.4 System Settings & Approval Rules

- Integrate with **System Settings** and **Approval Workflows**:
  - Control:
    - Thresholds for when P.R. or P.O. requires additional approval.
    - Which roles can approve P.R.s vs issue P.O.s for certain departments or amounts.
  - Store configuration centrally under **Financial Controls** in System Settings.

### 8.5 Audit & Governance Integration

- Ensure:
  - Creation, status changes, and key field edits for P.R. and P.O. are logged as audit events.
  - Audit Logs can filter by:
    - `log_name` = `procurement` or `configuration`.
    - Entity type (P.R. vs P.O.).
  - Clicking an audit entry can deep-link back to the P.R. or P.O. detail screen.

---

## 9. Summary

The **Procurement** module in LFS provides a clean, modular foundation for handling Purchase Requests and Purchase Orders, with clear status workflows and tight integration into Accounts Payable.  
As it evolves with deeper 3-way matching, configurable approval rules, richer analytics, and stronger audit integration, it will become the central control point for spend authorization and vendor commitments across the financial platform.

