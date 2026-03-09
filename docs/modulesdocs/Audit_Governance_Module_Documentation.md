# Audit & Governance Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Audit & Governance** module (implemented inside the **LFS Administration** area) provides platform-wide **observability and access control** for LFS.  
It covers:

- **Audit logging** of financial and system events.
- **Role & permission management** for all modules.
- Foundations for **approval workflows** and integration monitoring.

This document focuses on:

- How Audit & Governance is structured and implemented.
- Key features and workflows.
- How the navigation menus operate.
- Recommended enhancements to strengthen governance.

---

## 2. Tech Stack & Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/LFSAdministration`
- **Layers**:
  - `Domain`: `LFSAdministration` concepts (governance, security, observability).
  - `Application`: `LFSAdministrationController`, `LFSAdministrationOverview` (conceptual).
  - **Infrastructure**:
    - `Activity` model (from `spatie/laravel-activitylog`) for audit logs.
    - `IntegrationLog` model for integration events.
    - `Role`, `Permission` (from `spatie/laravel-permission`) for RBAC.
    - `PostingSource` (Core Accounting) for sync logs and idempotent postings.
  - **UI**: Blade views under `app/Modules/LFSAdministration/UI/Views`:
    - `index.blade.php` – Governance dashboard.
    - `audit-logs.blade.php` – Audit Logs.
    - `roles/index.blade.php`, `roles/edit.blade.php` – Role & Permission Management.
    - `integration-events.blade.php`, `sync-logs.blade.php` – Integration Center screens (cross-cutting with Integration module).
- **Service provider**: `LFSAdministrationServiceProvider` (not inspected, but responsible for registering routes/views).

Routing:

- `app/Modules/LFSAdministration/routes.php`
  - Prefix: `lfs-administration`
  - Name: `lfs-administration.*`
  - Middleware: `auth`, `verified`, `permission:lfs-administration.view`
  - Routes:
    - `GET /` → `index()` (Governance dashboard).
    - `GET /audit-logs` → `auditLogs()`.
    - `GET /integration-events` → `integrationEvents()`.
    - `GET /sync-logs` → `syncLogs()`.
    - `GET /roles` → `roles()`.
    - `GET /roles/{id}/edit` → `roleEdit()`.
    - `PUT /roles/{id}` → `roleUpdate()` (with `lfs-administration.manage`).

---

## 3. Key Components

### 3.1 LFSAdministrationController

Responsible for governance-related screens:

- `index()`
  - Renders **Governance, Security & Observability** dashboard.
  - Shows:
    - Recent **financial activities** (using `AuditService::LOG_FINANCIAL`).
    - Environment and framework version.

- `auditLogs(Request $request)`
  - Builds a query on `Activity` with optional filters:
    - `log_name` (category; e.g. `financial`, `default`).
    - `event` (partial match).
    - `from_date`, `to_date` on `created_at`.
  - Paginates results and passes them to `audit-logs` view.

- `roles()` / `roleEdit()` / `roleUpdate()`
  - Use `Spatie\Permission\Models\Role` and `Permission`:
    - `roles()` loads roles (with count of permissions) and all permissions.
    - `roleEdit()` loads a specific role, its permissions, and all permissions.
    - `roleUpdate()`:
      - Enforces `lfs-administration.manage`.
      - Syncs selected permissions onto the role.

- `integrationEvents()` / `syncLogs()`
  - Described in the **Integration Center** module documentation.
  - Provide integration observability (event status + posting sources).

### 3.2 Audit Logs (Activity Model)

- Logging:
  - `AuditService` records financial activities into `Activity` with:
    - `log_name` (e.g. `financial`).
    - `description`.
    - `event` and `properties`.
    - `causer` (user who performed the action).
- UI:
  - `audit-logs.blade.php` lists:
    - Date/time.
    - Category (`log_name`).
    - Event.
    - Description.
    - User (name/email).
- Filters:
  - Category, from/to dates (and optionally event if extended).

### 3.3 Role & Permission Management

- Roles:
  - Represent high-level access groups (CFO, Finance Manager, AR Officer, AP Officer, etc.).
  - Store permissions via `spatie/laravel-permission`.

- Permissions:
  - Fine-grained strings like:
    - `core-accounting.view`, `core-accounting.manage`.
    - `accounts-receivable.view`, `accounts-receivable.manage`.
    - `treasury.view`, `treasury.manage`, etc.
  - Mapped to menu visibility in `config/navigation.php`.

- UI:
  - `roles/index.blade.php`:
    - Lists roles with count of permissions.
    - Allows editing if user has `lfs-administration.manage`.
  - `roles/edit.blade.php`:
    - Shows all available permissions in a scrollable, checkbox-based grid.
    - Permissions assigned to the role are pre-checked.
    - On submit:
      - `roleUpdate()` syncs role’s permissions.

This provides a centralized **RBAC (Role-Based Access Control)** management console for the entire LFS platform.

---

## 4. Navigation Menus & Screens

Audit & Governance functionality is surfaced in two main areas:

1. **LFS Administration** menu (implementation).
2. **Audit & Governance** section in the UI Navigation Blueprint (design spec).

### 4.1 LFS Administration Dashboard (Governance, Security & Observability)

- Route: `GET /lfs-administration` → `index()`.
- Header: “Governance, Security & Observability”.
- Cards:
  - **Audit Logs**
    - Route: `/lfs-administration/audit-logs`.
    - Summary: “User activity and financial posting log”.
  - **Role & Permission Management**
    - Route: `/lfs-administration/roles`.
    - Summary: “Roles and permissions”.
- Recent financial events:
  - A list of recent financial audit entries (from `Activity` with `log_name = financial`).
  - With link: “View all audit logs” (pre-filtered to financial).
- Environment panel:
  - Shows environment (e.g. `local`, `staging`, `production`).
  - Shows Laravel version.

### 4.2 Audit Logs Screen

- Route: `GET /lfs-administration/audit-logs`.
- Filters:
  - Category (`log_name`):
    - All, Financial, Default (extensible).
  - From date / To date:
    - Date range filter on `created_at`.
- Table columns:
  - Date (timestamp).
  - Category (`log_name`).
  - Event.
  - Description.
  - User (causer name or email).
- Use cases:
  - **Operational audit** (who changed what, when).
  - **Financial governance** (who posted journals, closed periods, approved actions).

### 4.3 Role & Permission Management

- Roles list:
  - Route: `GET /lfs-administration/roles`.
  - Shows:
    - Role name.
    - Permissions count.
    - Edit action (when authorized).

- Role edit:
  - Route: `GET /lfs-administration/roles/{id}/edit`.
  - Permissions grid:
    - All permissions displayed with checkboxes.
    - Selected ones pre-checked for that role.
  - On save:
    - `PUT /lfs-administration/roles/{id}` updates role’s permissions.
- Use cases:
  - Assigning appropriate access to new roles (e.g. Implementation Consultant vs CFO).
  - Tightening or loosening permissions in response to **segregation of duties** requirements.

---

## 5. Workflows & Usage Patterns

### 5.1 Governance Workflow – Role Changes

1. **Identify requirement**:
   - New team member or changed responsibility.
2. **Role review**:
   - Use Role & Permission Management to:
     - View existing roles and their permission counts.
3. **Role edit**:
   - Admin (with `lfs-administration.manage`) opens the relevant role.
   - Adjusts permissions (add/remove).
   - Saves changes.
4. **Audit trail**:
   - Any role/permission changes are recorded in **Audit Logs** (via Activity).

### 5.2 Governance Workflow – Audit Review

1. Security or finance auditor navigates to **Audit Logs**.
2. Applies filters:
   - Category = `financial` for financial postings.
   - Date range for period under review.
3. Reviews:
   - Events (e.g. `journal.posted`, `period.closed`, `journal.reversed`).
   - Descriptions and users.
4. Cross-references:
   - With **Core Accounting** and **Integration Center** as needed for context.

---

## 6. Design Decisions & Guarantees

- **Centralized Governance**
  - Audit logs and role management are centralized in **LFS Administration**, ensuring:
    - Consistent governance policies across all modules.
    - Single pane of glass for security and audit.

- **Standards-Based Libraries**
  - Uses well-known Laravel packages:
    - `spatie/laravel-activitylog` for robust activity tracking.
    - `spatie/laravel-permission` for granular RBAC.

- **Permission-Driven UI**
  - Navigation items and routes are protected with permissions:
    - Prevents unauthorized access to governance tools.
    - Aligns with the UI Navigation Blueprint’s role-based menu visibility.

---

## 7. Recommended Enhancements

These are **optional** but useful improvements for the Audit & Governance module.

### 7.1 Finer-Grained Audit Categories & Filters

- Add more `log_name` categories (e.g. `security`, `integration`, `workflow`).
- Enhance filters with:
  - User selector.
  - Module or resource type.
  - Severity or event kind (create/update/delete/approval).

### 7.2 Audit Log Drill-Down

- Allow clicking an audit entry to:
  - View **before/after values** for key fields (where available).
  - Link directly to the underlying record (e.g. journal, invoice, bill).

### 7.3 Permission Grouping & Templates

- Group permissions in UI by module (AR, AP, Treasury, etc.).
- Provide **role templates** (e.g. “CFO”, “AR Officer”) with recommended permission sets.
- Optionally support **role cloning**.

### 7.4 Change Requests & Approvals for Role Edits

- Introduce a **change request** mechanism for roles:
  - Proposed changes vs approved changes.
  - Approval workflow for role/permission updates.

### 7.5 Retention & Export

- Configure retention policies:
  - E.g. retain financial audit logs for 7 years.
- Provide:
  - CSV/Excel export of audit logs for external auditors.

### 7.6 Security Analytics

- Dashboards for:
  - Login failures.
  - Privilege escalation attempts.
  - High-risk actions (mass journal postings, configuration changes).

### 7.7 System Settings Change Tracking & Surfacing

- Treat **System Settings** (see `System_Settings_Module_Documentation.md`) as a first-class audited resource:
  - Log every settings change to `Activity` with:
    - `log_name` (e.g. `configuration`).
    - Entity/group (e.g. `company_settings`, `financial_controls`, `tax_configuration`).
    - Before/after values for key fields (serialized in `properties`).
    - User, timestamp, and optional change reason.
- Extend the **Audit Logs** screen with:
  - A **“Configuration changes”** filter preset (by `log_name = configuration` or similar).
  - Clear labeling of System Settings events (module, setting group, and key fields changed).
  - Optional diff-style rendering for important settings (e.g. approval thresholds, period lock rules).
- Optionally add a dedicated **“Configuration Audit”** tab or view under LFS Administration:
  - Focused view over System Settings changes only.
  - Filters by setting group (Company, Financial Controls, Tax).
  - Direct links back to the corresponding System Settings screens (read-only or editable, based on permissions).

---

## 8. Summary

The Audit & Governance module in LFS:

- Centralizes **audit logs** and **role/permission management** under LFS Administration.
- Provides crucial infrastructure for **compliance, security, and oversight**.

With the recommended enhancements and the complementary **Approval Workflows** and **Integration Center** modules, it can form a comprehensive governance layer for the entire financial platform.

