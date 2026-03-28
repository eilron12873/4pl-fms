# MES General Settings Implementation Guide
## Exact Replica Blueprint (Livewire + Blade)

This document explains exactly how the current General Settings page is implemented so another AI agent can reproduce it with the same behavior, storage model, validations, and UI.

## Route and Access
- URL: `/admin/settings/general`
- Route name: `admin.settings.general`
- Route file: `routes/web.php`
- Route middleware: `auth`
- Component-level guard (hard gate): `abort_unless(auth()->user()?->isAdmin(), 403);`
  - Defined in `mount()` and repeated in mutating actions.

## Core Files
- Livewire component: `app/Livewire/Admin/Settings/GeneralSettings.php`
- Blade view: `resources/views/livewire/admin/settings/general-settings.blade.php`
- Settings model/storage service: `app/Models/SystemSetting.php`
- Default seed values: `database/seeders/SystemSettingSeeder.php`

## Architecture Overview

### Full-page Livewire module
- Uses `#[Layout('layouts.app')]`.
- Uses `WithFileUploads` for logo upload.
- One component owns all tabs and persists all settings via a single `save()` action.

### Tab model
`activeTab` values:
- `general`
- `branding`
- `localization`
- `system`
- `notifications`

Switching tabs is done in-view with `wire:click="$set('activeTab', '...')"`.

## Settings Storage Model (`SystemSetting`)

### Table shape
Logical schema expected by code:
- `key` (unique)
- `value` (JSON-encoded string)
- `group` (`general|branding|localization|system|notification`)

### Read/write contract
- `SystemSetting::get(key, default)` reads decoded value from memory cache.
- `SystemSetting::set(key, value, group)` writes JSON encoded values.
- `SystemSetting::invalidateCaches()` clears memory and Laravel cache key `system_settings.all`.

### Caching strategy
- Per-request in-memory cache (`$memoryCache` static).
- Backed by remember-forever Laravel cache (`system_settings.all`).
- Bulk save in `GeneralSettings::save()` sets `SystemSetting::$deferCacheInvalidation = true`,
  writes many keys, then invalidates cache once in `finally`.

## Component State Contract (`GeneralSettings.php`)

All settings are represented as public properties in the component.

### General
- `company_name`, `company_code`, `address`, `contact_number`, `email`, `website`, `tax_id`

### Branding
- `logo_path` persisted path
- `logo` temporary upload
- `system_name`, `report_header`

### Localization
- `timezone`, `date_format`, `time_format`, `language`

### System defaults
- `default_production_line_id`, `default_warehouse_id`, `default_uom`, `default_priority`
- `auto_create_job_order`, `require_qc_approval`, `enable_stage_validation`

### Notifications
- `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`
- `smtp_password_configured` helper flag
- `notify_overdue_jobs`, `notify_failed_inspection`, `notify_chemical_alerts`

## Lifecycle and Behavior

### `mount()`
- Permission check via `isAdmin()`.
- Calls `loadFromStorage()` to hydrate all properties from `SystemSetting`.

### `loadFromStorage()`
- Boots memory cache once.
- Loads strings via `stringVal()` helper.
- Loads nullable ints via `nullableInt()`.
- Casts boolean flags from stored values.
- Never displays stored SMTP password; sets `smtp_password = ''` and computes `smtp_password_configured`.

### `save()`
- Re-checks admin permission.
- Validates all fields using `rules()`.
- Logo branch:
  - if new logo uploaded, validate image/max 2MB
  - delete previous logo file from `public` disk
  - store new file under `settings/` in `public` disk
  - update `logo_path`
- Writes all setting keys grouped by category.
- Encrypts SMTP password only when user enters a non-empty new value:
  - stored at key `smtp_password_encrypted` via `Crypt::encryptString(...)`
  - blank value means “keep existing”.
- Invalidates settings cache once after all writes.
- Flashes success message.

### `removeLogo()`
- Permission check.
- Deletes existing logo file from public storage.
- Persists `logo_path = null`.
- Flashes success.

### Normalizers
- `updatedDefaultProductionLineId()` and `updatedDefaultWarehouseId()`
  - convert empty-string values from `<select>/<input>` to `null`
  - ensure integer typing when provided

## Validation Rules (Exact)
- `company_name`: required, string, max 255
- `company_code`: nullable, string, max 100
- `address`: nullable, string, max 2000
- `contact_number`: nullable, string, max 100
- `email`: nullable, email, max 255
- `website`: nullable, string, max 500
- `tax_id`: nullable, string, max 100
- `system_name`: required, string, max 255
- `report_header`: nullable, string, max 500
- `timezone`: required, must be in `timezone_identifiers_list()`
- `date_format`: required, string, max 50
- `time_format`: required, one of `12h|24h`
- `language`: required, string, max 10
- `default_production_line_id`: nullable integer exists in `production_lines.id` and `deleted_at IS NULL`
- `default_warehouse_id`: nullable integer min 0
- `default_uom`: required string max 50
- `default_priority`: required one of `urgent|high|normal|low`
- `auto_create_job_order`: boolean
- `require_qc_approval`: boolean
- `enable_stage_validation`: boolean
- `smtp_host`: nullable string max 255
- `smtp_port`: required integer between 1 and 65535
- `smtp_username`: nullable string max 255
- `smtp_password`: nullable string max 500
- `notify_overdue_jobs`: boolean
- `notify_failed_inspection`: boolean
- `notify_chemical_alerts`: boolean
- file upload validation on `logo`: image, max 2048 KB

## View Structure (`general-settings.blade.php`)

### Overall layout
- Top header with title + subtitle.
- One form wrapping tab content: `wire:submit.prevent="save"`.
- Sticky bottom save bar (fixed footer style) with `wire:click="save"` button.

### Tabs
Top tab strip with 5 tabs:
- General
- Branding
- Localization
- System defaults
- Notifications

### General tab UI
- Organization fields as responsive grid.
- Required marker on `company_name`.

### Branding tab UI
- Existing logo preview (if `logo_path` present).
- `Remove logo` action with confirmation.
- file upload input with loading state.
- `system_name` and `report_header`.

### Localization tab UI
- Timezone select from `timezone_identifiers_list()`.
- date format text field + helper text.
- time format select (`24h`, `12h`).
- language select (`en`, `tl`).

### System defaults tab UI
- `default_production_line_id` select from `$productionLines`.
- `default_warehouse_id` numeric input.
- `default_uom`, `default_priority`.
- Three boolean options as checkboxes.

### Notifications tab UI
- SMTP fields.
- Password field intentionally blank; helper text shown when configured.
- Three notification toggle checkboxes.

## External Data Dependency
- `ProductionLine` list loaded in `render()`:
  - ordered by `name`
  - selected columns: `id`, `name`, `code`
- Required for `default_production_line_id` dropdown.

## Seeder Defaults (`SystemSettingSeeder`)
Bootstraps the exact key set expected by the page, grouped by:
- `general`
- `branding`
- `localization`
- `system`
- `notification`

Important seeded defaults:
- `company_name = MES Manufacturing`
- `system_name = MES`
- `timezone = Asia/Manila`
- `date_format = Y-m-d`
- `time_format = 24h`
- `default_uom = pcs`
- `default_priority = normal`
- SMTP host/username empty, port 587
- boolean notification/system toggles pre-set

## Security and Secrets Handling
- SMTP password is never rendered back.
- Stored as encrypted string at `smtp_password_encrypted`.
- Empty password submission does not overwrite stored value.
- Page access and mutation actions are both role-gated.

## Exact Replica Acceptance Checklist
- Route `/admin/settings/general` resolves to Livewire component.
- Non-admin users receive 403.
- All five tabs render and switch correctly.
- Existing values load from `system_settings`.
- Save writes all keys and flashes success.
- Logo upload replaces old file and updates preview.
- Logo remove deletes file and clears stored path.
- SMTP password behaves as “replace only when non-empty”.
- Sticky save footer is present and functional.
- Validation errors appear next to relevant fields.

## Diff vs Typical Breeze/Jetstream Settings

This module is not a standard account/profile page. Keep these differences:
- **System-wide admin settings**, not per-user profile preferences.
- **Key-value settings table** (`system_settings`) instead of fixed columns on `users`.
- **Single Livewire component with tabbed sections** rather than multiple profile forms.
- **Encrypted SMTP secret management** within admin settings.
- **Logo file lifecycle** (replace + delete old file) built into save flow.
- **Cross-module defaults** (production line, QC requirement, stage validation) absent in default scaffolds.

## Known Edge Cases and Safeguards

### 1) `system_settings` table missing
- Symptom: SQL errors when loading/saving settings.
- Safeguard:
  - Ensure migration for settings table exists and has run.
  - In bootstrap scripts, seed defaults after migration.
- Quick checks:
  - `php artisan migrate:status`
  - verify `system_settings` table exists before calling `SystemSetting::get/set`.

### 2) Public storage symlink missing
- Symptom: logo uploads save successfully but image preview 404s.
- Cause: `asset('storage/...')` requires `public/storage` symlink.
- Safeguard:
  - Run `php artisan storage:link` in setup.
  - In deployment scripts, assert symlink existence.

### 3) `production_lines` table empty or unavailable
- Symptom: default production line dropdown empty; validation may fail for stale selected value.
- Safeguard:
  - Keep production line field optional (`nullable`).
  - Seed production lines before using this page for full functionality.
  - If selected ID becomes invalid, reset to `null`.

### 4) SMTP password handling pitfalls
- Symptom: password unintentionally wiped or exposed in UI.
- Safeguard:
  - Never prefill password input from storage.
  - Only overwrite encrypted password when new non-empty value is submitted.
  - Keep explicit `smtp_password_configured` flag for UX.

### 5) Corrupt JSON in `system_settings.value`
- Symptom: JSON decode exceptions when booting memory cache.
- Safeguard:
  - Keep writes via `SystemSetting::set()` only.
  - If manual DB edits are needed, ensure valid JSON payloads.
  - Add recovery script/command to re-seed defaults if corruption is detected.

### 6) Cache staleness after direct DB edits
- Symptom: UI still shows old values after manual SQL updates.
- Safeguard:
  - Invalidate settings cache after out-of-band DB changes.
  - Use `SystemSetting::invalidateCaches()` or clear app cache.

### 7) Old logo file orphaning
- Symptom: disk usage grows due to unused historical logo files.
- Safeguard:
  - Keep current replace flow (delete old file before storing new).
  - Optionally add periodic cleanup job for orphaned files under `storage/app/public/settings`.

### 8) Permission mismatch in cloned implementations
- Symptom: non-admin users can open/save settings.
- Safeguard:
  - Keep both route middleware and component-level `isAdmin()` abort checks.
  - Re-check permissions inside mutating methods (`save`, `removeLogo`).

## Prompt Template for Another AI Agent
```text
Replicate the General Settings page exactly as documented in `MES_General_Settings_Implementation_Guide.md`.
Use Laravel + Livewire full-page component architecture with tabs: general, branding, localization, system, notifications.
Implement route `/admin/settings/general` named `admin.settings.general`, auth middleware, and admin-only guard.
Persist settings in a key-value `system_settings` table via a `SystemSetting` model with cache-backed get/set methods.
Recreate logo upload/remove behavior, encrypted SMTP password storage, sticky save footer, and all validation rules.
Do not substitute with Breeze/Jetstream profile scaffolding.
```
