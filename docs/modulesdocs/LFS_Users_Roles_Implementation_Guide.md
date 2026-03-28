# LFS Users & Roles – Implementation Guide

This document maps the behaviors described in [MES_Users_Roles_Implementation_Guide.md](./MES_Users_Roles_Implementation_Guide.md) to the **4PL-FMS / LFS** stack: **Spatie Laravel Permission**, **Blade + controllers** (no Livewire), and **LFS Administration → System Settings**.

---

## Purpose

- Central **user account CRUD** (create, edit, activate/deactivate, delete).
- **Single primary Spatie role** per user (`syncRoles`) chosen from the web guard’s roles.
- **Safety rules** aligned with MES: Super Admin boundaries, last Super Admin protection, self-protection.
- **Audit** entries under `log_name = security` for identity changes.

---

## MES vs LFS (quick diff)

| MES | LFS (this project) |
|-----|-------------------|
| `users.role` enum column | **Spatie** `model_has_roles`; no duplicate enum on `users` |
| Livewire `/admin/users` | Blade routes under `/lfs-administration/settings/users` |
| `isAdmin()` gate | `UserPolicy` + permissions `lfs-administration.users.view` / `.manage` + Spatie roles **Super Admin** / **Admin** |
| Permission matrix on same page | **Separate screen**: Role & Permission Management (`lfs-administration.roles`) under **Audit & Governance** |

---

## Routes

| Method | Path | Name | Middleware |
|--------|------|------|------------|
| GET | `/lfs-administration/settings/users` | `lfs-administration.settings.users` | `auth`, `verified`, `lfs-administration.view`, `lfs-administration.users.view` |
| GET | `/lfs-administration/settings/users/create` | `lfs-administration.settings.users.create` | same |
| POST | `/lfs-administration/settings/users` | `lfs-administration.settings.users.store` | + `lfs-administration.users.manage` |
| GET | `/lfs-administration/settings/users/{user}/edit` | `lfs-administration.settings.users.edit` | `users.view` |
| PUT | `/lfs-administration/settings/users/{user}` | `lfs-administration.settings.users.update` | `users.manage` |
| DELETE | `/lfs-administration/settings/users/{user}` | `lfs-administration.settings.users.destroy` | `users.manage` |
| POST | `/lfs-administration/settings/users/{user}/toggle-active` | `lfs-administration.settings.users.toggle-active` | `users.manage` |

**Controller:** [`UsersManagementController`](../../app/Modules/LFSAdministration/UI/Controllers/UsersManagementController.php)  
**Route file:** [`app/Modules/LFSAdministration/routes.php`](../../app/Modules/LFSAdministration/routes.php)

---

## Permissions & module registration

Declared in [`app/Modules/LFSAdministration/module.json`](../../app/Modules/LFSAdministration/module.json):

- `lfs-administration.users.view` – list/search users.
- `lfs-administration.users.manage` – mutations (create/update/delete/toggle/role change).

Seeded via [`ModulePermissionsSeeder`](../../database/seeders/ModulePermissionsSeeder.php). After adding permissions, **re-run the seeder** (or sync roles) on existing environments so **Super Admin** and **Admin** receive the new permission names.

---

## Spatie roles (v1)

Seeded in [`ModulePermissionsSeeder`](../../database/seeders/ModulePermissionsSeeder.php). **Users & Roles** assigns exactly **one** web role per user (`syncRoles`).

| Role | Permission intent |
|------|-------------------|
| **Super Admin** | All web permissions. May manage any user and assign **Super Admin**. |
| **Admin** | Same as Super Admin in v1. **UserPolicy** blocks Admins from editing/deleting **Super Admin** users or assigning **Super Admin**. |
| **Manager** | Same as Super Admin **except** `lfs-administration.users.*` and integration API permissions (`integration.wms-billing`, `integration.financial-events`). |
| **Supervisor** | Every `*.view` plus `reports.view`, and **manage** on AR, AP, procurement, inventory valuation, and costing. |
| **Accountant** | Core accounting + GL + AR/AP + reporting + treasury (view/manage as listed) + billing + costing + fixed assets view + inventory valuation view. |
| **Analyst** | All **view**-style permissions (`*.view` and `reports.view`) only. |
| **Staff** | Narrow reads: GL, AR, AP, financial reporting, reports, procurement (view only). |

Operational roles do **not** receive `lfs-administration.users.view` / `.manage`, so they cannot open **Users & Roles** unless you change the seeder.

To narrow **Admin** or tune operational roles, edit the arrays / filters in `ModulePermissionsSeeder` and re-run the seeder (or use **Role & Permission Management** for one-off tweaks—re-seeding may overwrite those edits for roles defined in the seeder).

---

## UserPolicy rules

[`app/Policies/UserPolicy.php`](../../app/Policies/UserPolicy.php)

- **viewAny / view:** `lfs-administration.users.view` and role Super Admin **or** Admin.
- **create / update / delete / toggleActive:** `lfs-administration.users.manage` and role Super Admin **or** Admin, **and** target user must **not** have Super Admin unless the actor is Super Admin.
- **assignRoleName:** only Super Admin may assign role name **Super Admin**.
- **Last Super Admin:** cannot demote, deactivate, or delete if that would remove the final Super Admin.
- **Self:** cannot delete self; cannot deactivate self (controller + UI).

---

## Data model

[`users`](../../database/migrations/2026_03_28_220000_add_profile_fields_to_users_table.php) (incremental migration):

- `is_active` (boolean, default `true`)
- `department`, `position` (nullable strings)

**Login:** inactive users are rejected in [`LoginRequest::authenticate`](../../app/Http/Requests/Auth/LoginRequest.php) after a successful password check.

---

## Navigation

[`config/navigation.php`](../../config/navigation.php) → **System Settings** → **Users & Roles** (after **Company Settings**), `nav_key` `settings_users`, permission `lfs-administration.users.view` on the child item so [`NavigationService`](../../app/Core/Services/NavigationService.php) hides it for users without access.

---

## Audit

[`AuditService::LOG_SECURITY`](../../app/Core/Services/AuditService.php) with events such as `users.created`, `users.updated`, `users.deleted`, `users.active_toggled`. Properties include safe `before` / `after` snapshots (no passwords). Filter **Security** on [Audit Logs](../../app/Modules/LFSAdministration/UI/Views/audit-logs.blade.php).

Audit detail **subject link** for `App\Models\User` resolves to the user edit route via [`AuditSubjectLinkResolver`](../../app/Modules/LFSAdministration/Application/AuditSubjectLinkResolver.php).

---

## UI

Views under [`app/Modules/LFSAdministration/UI/Views/settings/users/`](../../app/Modules/LFSAdministration/UI/Views/settings/users/):

- **index** – summary cards (Super Admins / Admins / other), search, paginated table, toggle/delete where allowed.
- **create** / **edit** – standard forms; Super Admin role option only for Super Admin actors.

---

## Tests

[`tests/Feature/LFSAdministration/UsersManagementTest.php`](../../tests/Feature/LFSAdministration/UsersManagementTest.php)

---

## Operational checklist

1. Run migrations (adds `is_active`, `department`, `position`).
2. Run `php artisan db:seed --class=ModulePermissionsSeeder` (or full seed) so permissions and **Admin** role exist and roles stay in sync.
3. Ensure at least one **Super Admin** user remains for recovery.
4. Clear Spatie permission cache if you edit roles/permissions outside the app (`php artisan permission:cache-reset`).

---

## Related docs

- [MES_Users_Roles_Implementation_Guide.md](./MES_Users_Roles_Implementation_Guide.md) – UX and rule reference.
- [Audit_Governance_Module_Documentation.md](./Audit_Governance_Module_Documentation.md) – audit categories.

---

## Prompt template (LFS)

```text
Work in 4PL-FMS. User admin lives under LFS Administration → System Settings → Users & Roles.
Use UsersManagementController, UserPolicy, Spatie roles (Super Admin / Admin), and permissions
lfs-administration.users.view / .manage. Do not add a users.role enum; use syncRoles with one role.
Preserve last-Super-Admin and self-protection rules from LFS_Users_Roles_Implementation_Guide.md.
```
