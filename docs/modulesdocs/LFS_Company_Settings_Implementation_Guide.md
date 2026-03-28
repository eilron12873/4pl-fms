# LFS Company Settings – Implementation Guide

This document adapts the concepts from [MES_General_Settings_Implementation_Guide.md](./MES_General_Settings_Implementation_Guide.md) to the **LFS (4PL-FMS) Company Settings** screen: how it is wired today, how it differs from the MES pattern, and how to extend it safely.

---

## Purpose in FMS (4PL-FMS)

**Company Settings** is the **canonical company / financial presentation profile** for the tenant. It defines:

- **Legal and contact identity** – company name (used as the legal/trading label until a separate `trading_name` exists), address, registration/tax ID, phone, email, website.
- **Reporting context** – base currency (ISO 4217), reporting timezone, date format.
- **Fiscal anchors** – optional fiscal year start (month + day), validated as a real calendar date.
- **Branding** – optional logo (stored on the public disk, path in `company_logo`).

This aligns with [System_Settings_Module_Documentation.md](./System_Settings_Module_Documentation.md) **§3.1** (company profile). It does **not** replace sibling routes under **LFS Administration → System Settings**:

- **Financial Controls** – posting rules, backdating, manual journals (see `SystemSettingsController::financialControls`).
- **Tax Configuration** – rates, GL mapping (separate screens and tables).

Do not duplicate those concerns in this guide; consume them from their own modules.

---

## Specification coverage matrix (§3.1 vs implementation)

| Spec area (§3.1) | Status | Notes |
|------------------|--------|--------|
| Legal name, registration/tax ID, address, contact, website | **Implemented** | Single `company_name` column serves **both** legal and display/trading name until product adds `trading_name`. |
| Logo / branding | **Implemented** | Upload/remove on Company Settings; path in DB; `storage:link` required for browser URLs. |
| Base currency, timezone, fiscal start | **Implemented** | Currency validated against ISO 4217 via `symfony/intl` (`Currencies::getNames()`); timezone via `<select>`; fiscal month/day cross-validated with `checkdate()`. |
| Default decimal precision, document numbering | **Not in v1** | Treat as **future** or separate module settings; not columns on `general_settings` today. |

---

## Runtime consumers (source of truth)

| Consumer | Behaviour |
|----------|-----------|
| **Sidebar** ([`resources/views/layouts/sidebar.blade.php`](../../resources/views/layouts/sidebar.blade.php)) | Reads via [`SystemSettingsService::general()`](../../app/Core/Services/SystemSettingsService.php) so display matches cached admin saves. |
| **Company Settings UI** | Same service on GET/PUT. |
| **API** `GET /api/lfs-administration/settings/company` | [`LFSAdministrationApiController::settingsCompany`](../../app/Modules/LFSAdministration/UI/Controllers/LFSAdministrationApiController.php) uses `SystemSettingsService::general()`. |

**Integration backlog:** Domain modules (Core Accounting, AR/AP, reporting) should eventually read currency, timezone, and company labels through `SystemSettingsService::general()` (or thin wrappers) for amounts and PDF headers. **Recommended first consumer:** any shared PDF/report layout that already shows a company name—wire it to the same service as the sidebar.

**Cache consistency:** Prefer **all** reads through `SystemSettingsService::general()` after writes call `forgetGeneral()`, so behaviour stays consistent with the `Cache::rememberForever` key `system_settings.general`.

---

## Production / operations checklist

1. Run migrations; ensure one `general_settings` row exists (seeded).
2. Permissions: `lfs-administration.view` / `lfs-administration.manage` seeded for admin roles.
3. **Logo:** run `php artisan storage:link`; ensure `storage/app/public` (and `company/` uploads) are writable by the web user.
4. **Audit:** Configuration changes appear under Audit Logs → **Configuration** (see [`audit-logs`](../../app/Modules/LFSAdministration/UI/Views/audit-logs.blade.php) filtering).
5. Optional: keep [System_Settings_Module_Documentation.md](./System_Settings_Module_Documentation.md) “current state” in sync with shipped screens (high-level product doc).

---

## Acceptance criteria (production-ready Company Settings)

- **Required fields policy:** `company_name` is required when `config('company.require_company_name')` is true (default); override via `COMPANY_SETTINGS_REQUIRE_NAME=false` if needed.
- **Validation:** Currency is uppercase ISO 4217; website is `http`/`https` URL; fiscal month and day are both set or both empty, and form a valid calendar date.
- **Logo lifecycle:** Upload replaces prior file (old file deleted); remove clears DB path and deletes file; audit records `company_logo` before/after **paths** (not file bytes).
- **Cache:** `forgetGeneral()` after successful save.
- **Tests:** Feature tests cover validation failures, logo replace + `Storage::fake`, read-only GET without edit form.
- **Non-admin consumer:** At least one non-settings UI path reads company data via `SystemSettingsService::general()` (sidebar).

---

## Mapping: MES concepts → LFS Company Settings

| MES (MES guide) | LFS Company Settings (this project) |
|-----------------|--------------------------------------|
| URL `/admin/settings/general` | `GET /lfs-administration/settings/company` |
| Route name `admin.settings.general` | `lfs-administration.settings.company` (update: `lfs-administration.settings.company.update`) |
| `auth` + `isAdmin()` | `auth`, `verified`, `permission:lfs-administration.view` (GET); `lfs-administration.manage` required to save |
| Livewire full-page + tabs | **Blade** + classic form POST/PUT (single page, no tabs yet) |
| Key–value `system_settings` (`key`, `value` JSON, `group`) | **Single-row** table `general_settings` with typed columns ([`GeneralSetting`](../../app/Models/GeneralSetting.php)) |
| `SystemSetting::get/set` + dual cache | [`SystemSettingsService::general()`](../../app/Core/Services/SystemSettingsService.php) + `Cache::rememberForever` + `forgetGeneral()` |
| Five tabs (general, branding, localization, system, notifications) | **One consolidated form**: profile + branding (logo) + localization + fiscal hints |
| Logo upload in Branding tab | **Implemented** on Company Settings: multipart form, `storage/app/public/company`, audit paths |
| Encrypted SMTP in Notifications | **Out of scope** for Company Settings; use Laravel mail `.env` / config or a future dedicated module |
| MES defaults (production line, QC, stage validation) | **N/A** for LFS finance product; operational defaults live in domain modules (e.g. warehouses in Inventory) |

---

## Route and access

- **URL:** `/lfs-administration/settings/company`
- **Route names:**  
  - `lfs-administration.settings.company` (GET)  
  - `lfs-administration.settings.company.update` (PUT)
- **Route file:** [`app/Modules/LFSAdministration/routes.php`](../../app/Modules/LFSAdministration/routes.php)
- **Middleware (group):** `auth`, `verified`, `permission:lfs-administration.view`
- **Save route:** additionally `permission:lfs-administration.manage`
- **Controller gate for writes:** `$this->authorize('lfs-administration.manage')` in `companyUpdate()` (Spatie permission, not a custom `isAdmin()`).

**Read-only users:** Users with only `lfs-administration.view` see a summary panel without the edit form (see view below).

---

## Core files

| Concern | Path |
|--------|------|
| HTTP controller | [`app/Modules/LFSAdministration/UI/Controllers/SystemSettingsController.php`](../../app/Modules/LFSAdministration/UI/Controllers/SystemSettingsController.php) (`company`, `companyUpdate`) |
| Blade view | [`app/Modules/LFSAdministration/UI/Views/settings/company.blade.php`](../../app/Modules/LFSAdministration/UI/Views/settings/company.blade.php) |
| Eloquent model | [`app/Models/GeneralSetting.php`](../../app/Models/GeneralSetting.php) |
| Cached accessor for admin UI | [`app/Core/Services/SystemSettingsService.php`](../../app/Core/Services/SystemSettingsService.php) |
| Company policy config | [`config/company.php`](../../config/company.php) (`require_company_name`) |
| ISO 4217 currency list | Composer package **`symfony/intl`** (`Symfony\Component\Intl\Currencies`) |
| Persistence migration | [`database/migrations/2026_03_28_200000_create_general_settings_table.php`](../../database/migrations/2026_03_28_200000_create_general_settings_table.php) (includes seed row) |
| Configuration audit trail | [`AuditService::LOG_CONFIGURATION`](../../app/Core/Services/AuditService.php) + `settings.company.updated` in Activity |
| Read-only API (optional consumers) | `GET /api/lfs-administration/settings/company` in [`app/Modules/LFSAdministration/api.php`](../../app/Modules/LFSAdministration/api.php) |

---

## Architecture overview

### Controller + Blade (not Livewire)

- One **GET** action loads `$settings = $this->settings->general()` and passes `canManage` and grouped **timezone identifiers** for the select.
- One **PUT** action validates input (including logo / `remove_logo`), updates the row, **invalidates** the general settings cache, and writes an **audit** entry with `before` / `after` snapshots (including `company_logo` path).

### Storage model (`general_settings`)

Logical columns used by Company Settings (see model `$fillable`):

- **Profile:** `company_name`, `company_address`, `registration_number`, `telephone_number`, `email_address`, `website`
- **Branding:** `company_logo` (path under `storage/app/public`, exposed via `logo_url` accessor)
- **Localization / presentation:** `default_timezone`, `default_date_format`, `default_currency` (ISO 4217, 3 letters)
- **Fiscal identity (optional):** `fiscal_year_start_month`, `fiscal_year_start_day`

**Pattern:** single canonical row (migration seeds one row). `SystemSettingsService::general()` creates a row if missing.

### Caching strategy (MES-style “invalidate after bulk write” simplified)

- **Read path:** `Cache::rememberForever(SystemSettingsService::CACHE_GENERAL, ...)`.
- **Write path:** after `update`, call `SystemSettingsService::forgetGeneral()` so the next read hits the database.
- **Sidebar and integrations:** use `SystemSettingsService::general()` so cached values stay aligned with invalidation after admin saves.

---

## Lifecycle and behavior

### GET `company()`

- No `authorize('manage')` required.
- Resolves `canManage` for the view.
- Does not mutate state.

### PUT `companyUpdate()`

- `authorize('lfs-administration.manage')`.
- Validates request (see next section), including optional logo and `remove_logo`.
- Snapshots `$before` / `$after` for audited keys **including** `company_logo` (paths only).
- `forgetGeneral()`.
- Logs via `AuditService::log()` with `log_name = configuration`, event `settings.company.updated`.
- Redirects back with flash `success`.

### MES parity features **not** yet implemented on this page

- **Tabbed UI:** optional refactor to Livewire or multiple Blade routes if the form grows.
- **Searchable timezone picker:** v2 enhancement; v1 uses grouped `<select>`.

---

## Validation rules (current implementation)

Aligned with [`SystemSettingsController::companyUpdate`](../../app/Modules/LFSAdministration/UI/Controllers/SystemSettingsController.php):

| Field | Rules |
|-------|-------|
| `company_name` | Required if `config('company.require_company_name')`; else nullable; string, max 255 |
| `company_address` | nullable, string, max 2000 |
| `telephone_number` | nullable, string, max 64 |
| `email_address` | nullable, email, max 255 |
| `website` | nullable, string, max 255, valid URL, scheme `http` or `https` |
| `default_timezone` | **required**, `timezone` |
| `default_date_format` | **required**, string, max 32 |
| `default_currency` | **required**, string, size 3, **ISO 4217** (Symfony Intl currency list), normalized to uppercase |
| `registration_number` | nullable, string, max 128 |
| `fiscal_year_start_month` | nullable, integer, 1–12 |
| `fiscal_year_start_day` | nullable, integer, 1–31 |
| Fiscal pair | If either month or day is set, both required; must be a valid calendar date (e.g. not 31 June) |
| `company_logo` | nullable, image, max 2048 KB |
| `remove_logo` | optional boolean |

---

## View structure (`company.blade.php`)

- **Layout:** `x-app-layout` (Breeze-style), header with title + Back link.
- **Success flash** when present.
- **If `$canManage`:** `<form enctype="multipart/form-data">` with `@method('PUT')`, CSRF, `@error` on every field, logo preview + file input + remove checkbox, timezone `<select>` (grouped).
- **Else:** read-only summary and a short message that edit requires `lfs-administration.manage`.

There is **no** sticky footer or tab strip (unlike MES); submit is a standard inline **Save** button.

---

## Security and governance

- **Permissions:** Spatie `lfs-administration.view` / `lfs-administration.manage` (see [module permissions](../../app/Modules/LFSAdministration/module.json)).
- **Audit:** Every successful save emits a configuration activity (filter **Configuration** on Audit Logs).
- **Secrets:** Company Settings does **not** store SMTP passwords; keep mail credentials in `.env` / secrets manager.

---

## External dependencies

- **Navigation:** [`config/navigation.php`](../../config/navigation.php) → System Settings → Company Settings → `lfs-administration.settings.company`.
- **Chart of accounts:** not required for Company Settings (tax GL mapping lives under **Tax Configuration**).

---

## Edge cases and safeguards (adapted from MES)

### 1) `general_settings` table missing or empty before migration

- **Symptom:** SQL errors or empty UI.
- **Safeguard:** Run migrations; seed creates an initial row. `SystemSettingsService::general()` can create a row if none exists.

### 2) Public storage symlink missing (logo)

- **Symptom:** `asset('storage/...')` 404 for logos.
- **Safeguard:** `php artisan storage:link`; document in deployment checklist (same as MES §Known Edge Cases).

### 3) Cache shows stale company data after manual SQL

- **Symptom:** Admin UI or API still shows old values.
- **Safeguard:** `php artisan cache:forget` the key `system_settings.general` or call `SystemSettingsService::forgetGeneral()` from tinker; prefer updates through the app.

### 4) Fiscal start day invalid for chosen month (e.g. 31 June)

- **Symptom:** Rejected at validation with a clear error (`checkdate()`).

### 5) Permission mismatch

- **Symptom:** Users can view but should not edit (or the opposite).
- **Safeguard:** Keep route middleware + `authorize()` on `companyUpdate`; do not rely on hiding buttons alone.

### 6) JSON vs column model

- **MES** uses JSON values per key; **LFS** uses columns. Do not mix patterns on the same screen without a clear boundary—if you need arbitrary keys, introduce a separate `system_settings` KV table or namespace keys in a JSON column with a dedicated service.

---

## Diff vs MES General Settings (summary)

Keep these mental distinctions when porting ideas from the MES doc:

- **LFS is finance-first:** currency, fiscal year, registration/tax ID—not production lines, QC gates, or stage validation.
- **LFS uses RBAC permissions**, not a single `isAdmin()` flag.
- **LFS Company Settings is one Blade form** today, not a tabbed Livewire module.
- **Audit and configuration log stream** are first-class (MES guide does not emphasize the same Activity integration).
- **SMTP / notification toggles** belong outside this screen unless you explicitly expand scope.

---

## Related documentation

- [System_Settings_Module_Documentation.md](./System_Settings_Module_Documentation.md) – product scope for Company / Financial / Tax.
- [Audit_Governance_Module_Documentation.md](./Audit_Governance_Module_Documentation.md) – configuration change surfacing (§7.7).
- [Audit_Environment_Settings_Guide.md](./Audit_Environment_Settings_Guide.md) – audit export / retention env vars.

---

## Prompt template for another AI agent (LFS)

```text
Work in the 4PL-FMS / LFS codebase. Extend or debug Company Settings as documented in
`docs/modulesdocs/LFS_Company_Settings_Implementation_Guide.md`.

Constraints:
- Routes live under `lfs-administration` with names `lfs-administration.settings.company` and `.update`.
- Use `SystemSettingsController` and `SystemSettingsService`; persist to `general_settings` via `GeneralSetting`.
- After any write, call `forgetGeneral()` and log changes with `AuditService` using `LOG_CONFIGURATION` and event `settings.company.updated` (or a new event name if adding a distinct action).
- Respect `lfs-administration.view` for read and `lfs-administration.manage` for write.
- Do not replace this with Breeze profile settings; this is tenant-wide company configuration.
- Logo: store under `public` disk `company/`; delete previous file on replace; include `company_logo` in audit before/after; document `storage:link`.
```
