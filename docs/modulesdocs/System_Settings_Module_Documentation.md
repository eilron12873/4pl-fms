> LFS – Logistics Financial System  
> **System Settings Module – Technical & Functional Specification**

---

## 1. Purpose & Scope

The **System Settings** module is the central place where platform-wide configuration for LFS is managed.  
Conceptually, it lives under **LFS Administration → System Settings** and covers:

- **Company Settings** – legal entity profile, fiscal year, base currency, timezone, branding.
- **Financial Controls** – period lock rules, posting policies, approval thresholds, risk flags.
- **Tax Configuration** – VAT/GST rates, withholding rules, and mapping to GL accounts.

In the current codebase, System Settings is primarily a **navigation and design concept**:

- The navigation blueprint and configuration define how System Settings appears in the UI.
- Actual, fully-featured settings screens and persistent settings storage are **not yet implemented**.

This document explains:

- How the System Settings module is structured conceptually and wired into the UI.
- The intended tech stack & architecture once fully implemented.
- The target features and workflows.
- How each System Settings navigation menu is meant to operate.
- Recommended enhancements to evolve it from concept to a robust, production-ready module.

---

## 2. Tech Stack & Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Primary location (UI & governance)**: `app/Modules/LFSAdministration`
- **Navigation configuration**: `config/navigation.php`
- **Design blueprint**: `docs/LFS_UI_Navigation_Blueprint.md` (Section “1️⃣3️⃣ System Settings”)

### 2.1 Module Positioning

- System Settings is treated as part of the **LFS Administration** area:
  - Secured by `auth`, `verified`, and `permission:lfs-administration.view`.
  - Uses the same governance foundation as **Audit & Governance** and **Approval Workflows**.
- In `config/navigation.php`:
  - There is a top-level group:
    - **Label**: `System Settings`
    - **Icon**: `fas fa-cogs`
    - **Permission**: `lfs-administration.view`
  - Child items:
    - **Company Settings** – `nav_key: settings_company`
    - **Financial Controls** – `nav_key: settings_financial_controls`
    - **Tax Configuration** – `nav_key: settings_tax`
  - Today, all three children route to `lfs-administration.index` as **placeholders**.

### 2.2 Intended Settings Storage Model (Conceptual)

Although not yet implemented, the intended model for System Settings should:

- Use a **single source of truth** for configurable platform options, such as:
  - `company_name`, `registration_number`, `base_currency`, `fiscal_year_start`, `timezone`.
  - `period_lock_policy`, `max_backdating_days`, `approval_thresholds`.
  - `vat_rates`, `withholding_tax_rates`, `tax_account_mappings`.
- Be stored in the database (e.g. `system_settings` or multiple settings tables) with:
  - Key-value or JSON-based structure.
  - Per-tenant isolation (one LFS instance per customer, consistent with the overall deployment model).
  - Caching to reduce lookup overhead (e.g. via Laravel cache).

### 2.3 Integration With Other Modules (Conceptual)

System Settings will be **read by** other modules at runtime, for example:

- **Core Accounting**:
  - Period lock rules (e.g. allow posting only within X days of period close).
  - Base currency and fiscal year configuration.
- **Accounts Receivable / Accounts Payable**:
  - Credit limit rules or thresholds for approvals.
  - Tax calculation policies (e.g. inclusive vs exclusive tax, rounding rules).
- **Tax / Reporting**:
  - VAT and withholding tax rate tables.
  - Mapping of tax codes to GL accounts.
- **Approval Workflows**:
  - Threshold-based approval routing (e.g. invoices over X require manager approval).

---

## 3. Key Components (Conceptual Design)

The System Settings module is conceptually split into three functional areas.

### 3.1 Company Settings

Intended responsibilities:

- Store **master data** for the company profile:
  - Legal name and trading name.
  - Registration/tax ID numbers.
  - Address and contact details.
  - Logo/branding parameters.
- Define **financial identity**:
  - Base / presentation currency (e.g. `USD`, `EUR`).
  - Fiscal year start month and day.
  - Default timezone for financial reporting.
- Configure **global defaults**:
  - Default decimal precision.
  - Default document numbering patterns (if not overridden at module level).

### 3.2 Financial Controls

Intended responsibilities:

- Central place to configure **financial discipline rules**:
  - **Period lock policies**:
    - When can a period be reopened?
    - Max days allowed for back-dated postings.
  - **Posting policies**:
    - Whether certain journals must be approved (when Approval Workflows is fully implemented).
    - Whether manual journals are allowed at all, or limited to specific roles.
  - **Approval thresholds**:
    - Invoice and vendor bill approval thresholds by amount.
    - Credit note approval rules.
  - **Risk controls**:
    - Flags for high-risk customers/vendors.
    - Requirements for dual-approval on certain operations.

### 3.3 Tax Configuration

Intended responsibilities:

- Define **tax regimes** relevant to the company:
  - VAT/GST percentage rates.
  - Withholding tax percentages and bases (e.g. on services vs goods).
  - Exemption rules and thresholds.
- Map **tax codes to GL accounts**:
  - Input VAT / Output VAT accounts.
  - Withholding tax payable/receivable accounts.
- Configure **calculation behavior**:
  - Inclusive vs exclusive tax.
  - Rounding strategies (per line vs per invoice, round-half-up vs banker's rounding).
- Provide a **central reference** for AR and AP modules when calculating and posting taxes.

---

## 4. Navigation Menus & Screens

System Settings is visible in the LFS UI as a dedicated section, following the **LFS UI Navigation Blueprint**.

### 4.1 System Settings Group

- **Location**: Main sidebar, typically under the administration/governance area.
- **Navigation definition**: `config/navigation.php`
  - Group:
    - Label: `System Settings`
    - Icon: `fas fa-cogs`
    - Order: `130`
    - Permission: `lfs-administration.view`
- **Behavior today**:
  - Clicking any child item currently routes to the LFS Administration dashboard (`lfs-administration.index`).
  - This provides a placeholder while screens are being designed and implemented.

### 4.2 Company Settings Menu

- **Blueprint**: `docs/LFS_UI_Navigation_Blueprint.md` – “13.1 Company Settings”.
- **Intended route** (future):
  - `GET /lfs-administration/settings/company`
  - Guarded by `permission:lfs-administration.manage` for editing; `view` for read-only access.
- **Intended screen behavior**:
  - Show current company profile, fiscal year, currency, and timezone.
  - Allow editing via a form with:
    - Validation (e.g. valid currency codes, timezone identifiers).
    - Preview for logo/branding if implemented.
  - Show **read-only** view for users with view-only rights (e.g. auditors).

### 4.3 Financial Controls Menu

- **Blueprint**: “13.2 Financial Controls”.
- **Intended route** (future):
  - `GET /lfs-administration/settings/financial-controls`
  - Editing restricted to senior roles (e.g. CFO, Finance Manager) via `lfs-administration.manage`.
- **Intended screen behavior**:
  - Sections for:
    - Period lock policies.
    - Posting policies (manual journal restrictions, back-dating limits).
    - Approval thresholds for AR/AP/Fixed Assets (if Approval Workflows is active).
  - Clear warnings when changing controls that impact live posting behavior.
  - Integration with **Audit Logs**, so all changes are recorded.

### 4.4 Tax Configuration Menu

- **Blueprint**: “13.3 Tax Configuration”.
- **Intended route** (future):
  - `GET /lfs-administration/settings/tax`
  - Managed by authorized finance roles with `lfs-administration.manage`.
- **Intended screen behavior**:
  - Maintain a catalog of:
    - Tax codes (e.g. `VAT_5`, `VAT_15`, `WHT_10`).
    - Associated rates and applicability rules.
  - Configure:
    - Related GL accounts (input/output VAT, withholding payable/receivable).
    - Inclusive/exclusive flags and rounding rules.
  - Provide:
    - Safe editing workflow (draft vs active tax rates).
    - Effective-from dates for rate changes.

---

## 5. Workflows & Usage Patterns (Target Design)

Although System Settings screens are not yet implemented, the **intended workflows** are:

### 5.1 Initial Implementation / Go-Live Setup

1. **Company Settings**:
   - Implementation consultant configures:
     - Company name, address, registration numbers.
     - Base currency and fiscal year start.
     - Timezone and any branding options.
2. **Financial Controls**:
   - CFO/Finance Manager defines:
     - Period lock behaviors and back-dating limits.
     - Which modules require approvals and at what thresholds.
3. **Tax Configuration**:
   - Tax/Finance specialist configures:
     - Standard VAT/GST and withholding tax rates.
     - Account mappings for tax postings.
4. **Validation**:
   - Key modules (AR, AP, Core Accounting, Treasury) are tested end-to-end to confirm:
     - Correct tax behavior.
     - Respect for lock and approval rules.

### 5.2 Ongoing Operations

Typical operations once System Settings is live:

- Update **tax rates** when regulations change, with effective dates.
- Adjust **approval thresholds** as transaction volumes and risk appetite evolve.
- Fine-tune **posting and period lock policies** based on audit recommendations.
- View Company Settings in read-only mode for:
  - Auditors.
  - Implementation partners.
  - Internal stakeholders who need to confirm configuration.

### 5.3 Governance & Control

- All write operations in System Settings should:
  - Require elevated permissions (`lfs-administration.manage` or equivalent).
  - Be logged in **Audit Logs** with before/after snapshots.
  - Optionally require **dual-control** (two-person rule) for high-risk changes, via Approval Workflows.

---

## 6. Design Decisions & Guarantees (Target)

Once fully implemented, System Settings is intended to provide:

- **Centralized configuration**:
  - Single source for global financial and tax configuration.
  - Eliminates scattered hard-coded values across modules.
- **Auditability**:
  - Every change traceable to a user, time, and previous value.
  - Integrated with the Audit & Governance module.
- **Security & Segregation of Duties**:
  - Only specific roles can change System Settings.
  - Read vs write permissions can be separated.
- **Predictable behavior**:
  - Modules must respect System Settings as their configuration source for:
    - Period locks.
    - Approval thresholds.
    - Tax behavior.

---

## 7. How the Module Was Created (Current State)

In the current iteration of LFS:

- System Settings exists primarily as:
  - A **navigation group** and menu items in `config/navigation.php`.
  - A **design blueprint** in `docs/LFS_UI_Navigation_Blueprint.md` (Section 13).
  - A conceptual dependency referenced by:
    - **Approval Workflows** (for approval thresholds and rules).
    - **Audit & Governance** (for viewing and securing configuration).
- There is **no dedicated controller or views** yet for:
  - `/lfs-administration/settings/company`
  - `/lfs-administration/settings/financial-controls`
  - `/lfs-administration/settings/tax`
- Settings that do exist are currently expressed via:
  - Static configuration files (`config/*.php`).
  - Environment variables (`.env`) for infrastructure and base application behavior.

The next evolution is to introduce persistent, UI-managed System Settings as described above.

---

## 8. Recommended Enhancements

The following enhancements will turn System Settings from a conceptual module into a fully functional configuration center.

### 8.1 Implement a System Settings Data Model

- Create one of the following patterns:
  - **Generic key-value table**:
    - `system_settings (id, key, value, type, group, created_at, updated_at)`.
  - **Structured settings tables**:
    - `company_settings`, `financial_control_settings`, `tax_settings`, etc.
- Add:
  - Caching for frequently used settings.
  - Helper service, e.g. `SystemSettingsService::get('financial_controls.max_backdating_days')`.

### 8.2 Build Dedicated Settings Screens

- Implement new controller methods and Blade views in `LFSAdministration`:
  - `companySettingsIndex()` / `updateCompanySettings()`.
  - `financialControlsIndex()` / `updateFinancialControls()`.
  - `taxSettingsIndex()` / `updateTaxSettings()`.
- Wire routes such as:
  - `GET /lfs-administration/settings/company`
  - `GET/POST /lfs-administration/settings/financial-controls`
  - `GET/POST /lfs-administration/settings/tax`
- Update `config/navigation.php` to point each menu to its dedicated route.

### 8.3 Strong Validation & Safe Change Management

- Add robust validation rules:
  - Valid ISO currency codes and timezones.
  - Reasonable numeric ranges for thresholds and tax rates.
- Support **draft vs active** configuration:
  - Allow preparing configuration in draft.
  - Apply changes at a specific time or period boundary.

### 8.4 Deep Integration With Audit & Governance

- For every settings change:
  - Log to `Activity` with:
    - Previous values.
    - New values.
    - User and timestamp.
    - Optional reason/comment.
- Provide **diff views** in Audit Logs for configuration changes.

### 8.5 Integration With Approval Workflows

- For high-risk settings (e.g. period lock policies, approval thresholds):
  - Require approval via the **Approval Workflows** module:
    - Settings change request → pending approval → approval → apply.
- Allow configuration in System Settings to determine:
  - Which setting groups require multi-step approval.

### 8.6 Expose Read-Only APIs (Optional)

- Provide read-only API endpoints (secured via `auth:sanctum`) for:
  - Company profile and fiscal configuration.
  - Tax configuration & rates.
  - Financial controls that external systems may need to respect.

---

## 9. Summary

The **System Settings** module is a **cross-cutting configuration center** for LFS, designed to centralize company profile, financial controls, and tax configuration.  
Today, it is represented by navigation entries and blueprints, with actual screens and persistence still to be implemented.  
By introducing a proper settings data model, dedicated screens, strong validation, and tight integration with Audit & Governance and Approval Workflows, System Settings can become a powerful, audit-ready control hub for the entire financial platform.

