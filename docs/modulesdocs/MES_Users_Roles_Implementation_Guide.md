# MES Users & Roles Implementation Guide
## Exact Replica Blueprint (Livewire + Blade)

This document explains exactly how the Users & Roles page is implemented so another AI agent can reproduce it with the same behavior and UX.

## Purpose
- Manage user accounts (create, edit, activate/deactivate, delete)
- Enforce role boundaries around `super_admin`
- Provide role distribution visibility via summary cards

## Route and Entry Point
- URL: `/admin/users`
- Route name: `admin.users`
- Route declaration: `routes/web.php`
  - `Route::get('/admin/users', AdminUsersRoles::class)->name('admin.users');`
- Navigation link:
  - `resources/views/livewire/navigation.blade.php`
  - Section: Admin
  - `route('admin.users')`

## Core Files
- Livewire component: `app/Livewire/Admin/UsersRoles.php`
- View: `resources/views/admin/users-roles.blade.php`
- Model: `app/Models/User.php`
- Role/seed baseline: `database/seeders/UserRoleSeeder.php`
- User role schema migration: `database/migrations/2025_12_22_000000_add_role_to_users_table.php`

## Access Control Model

### Page-level access
- Guard in `mount()`:
  - `abort_unless(auth()->user()?->isAdmin(), 403);`
- Meaning:
  - `super_admin` and `admin` can access.
  - Others are blocked.

### Privilege rules inside actions
- Only `super_admin` can:
  - edit `super_admin` accounts
  - assign `super_admin` role
  - activate/deactivate `super_admin` accounts
  - delete `super_admin` accounts
- Last super-admin protection:
  - cannot demote/delete if it would remove final `super_admin`
- Self-protection:
  - cannot deactivate own account
  - cannot delete own account

## User Model Contract (`User.php`)

### Required user columns
- `role` enum: `super_admin|admin|manager|supervisor|operator`
- `is_active` boolean
- `department` nullable string
- `position` nullable string

### Role helpers used by page
- `isSuperAdmin()`
- `isAdmin()`
- `hasRole()`
- `hasAnyRole()`
- `role_name` accessor for display badge text

## Livewire Component Design (`UsersRoles.php`)

### Traits and layout
- Uses `WithPagination`
- Full-page layout via `#[Layout('layouts.app')]`

### Public state
- Search/filter:
  - `search`
- Edit state:
  - `editingUserId`
  - `editName`, `editEmail`, `editRole`, `editIsActive`, `editDepartment`, `editPosition`
- Create modal state:
  - `showCreateModal`
  - `createName`, `createEmail`, `createPassword`, `createPasswordConfirmation`
  - `createRole`, `createIsActive`, `createDepartment`, `createPosition`
- Query string:
  - `$queryString = ['search'];`

### Render payload
- `users` paginated list:
  - query with search over `name`, `email`, `department`, `position`
  - sorted by `role`, then `name`
  - pagination size: `15`
- `roleStats`:
  - counts per role for summary cards

### Actions
- `openCreateModal()`, `cancelCreate()`
- `createUser()`
  - validates fields
  - password rule: `Password::defaults()`
  - role whitelist validation
  - blocks non-super-admin from creating super-admin
- `editUser($id)`, `cancelEdit()`, `updateUser()`
  - validates fields
  - blocks non-super-admin from super-admin edits
  - protects last super-admin from demotion
- `toggleUserStatus($id)`
  - blocks self-deactivation
  - blocks non-super-admin from super-admin status change
- `deleteUser($id)`
  - blocks self-delete
  - blocks non-super-admin from super-admin delete
  - protects last super-admin
  - catches FK violations (`QueryException`) and flashes friendly error

## View Structure (`users-roles.blade.php`)

## Top Bar
- Page title: `Users & Roles Management`
- Primary action button: `Create user`

## Flash Messages
- Success alert block
- Error alert block

## Summary Cards (3)
- Super Admins count
- Admins + Managers combined count
- Supervisors + Operators combined count

## User Table Section
- Header with total count and search input
- Columns:
  - User (avatar initials + name + email)
  - Role (color-coded badge)
  - Department
  - Position
  - Status
  - Actions
- Empty state row: `No users found.`
- Pagination footer with `{{ $users->links() }}`

## Status UX
- Active/Inactive displayed as badge
- Status badge can be clicked (toggle) only when permitted by guard conditions

## Actions UX
- `Edit` shown only when permitted
- `Delete` shown conditionally based on:
  - not self
  - not deleting final super-admin
  - role permission for super-admin target
- Delete uses `wire:confirm` prompt

## Create Modal
- Trigger: `showCreateModal`
- Fields:
  - name, email, password, confirm password, role, department, position, active checkbox
- Role options:
  - `super_admin` option shown only if current user is super-admin
- Submit: `wire:submit.prevent="createUser"`

## Edit Modal
- Trigger: `editingUserId`
- Fields:
  - name, email, role, department, position, active checkbox
- Role options:
  - `super_admin` option shown only to super-admin users
- Submit: `wire:submit.prevent="updateUser"`

## Role Definition Panel
- Static explanatory card at bottom describing each role

## Data Schema Requirements

The page assumes these `users` table columns exist:
- `role` (enum)
- `is_active` (boolean)
- `department` (nullable string)
- `position` (nullable string)

Migration source:
- `database/migrations/2025_12_22_000000_add_role_to_users_table.php`

If missing, this page will fail on query/assignment.

## Known Edge Cases and Safeguards

### 1) Missing role columns on `users` table
- Symptom: SQL errors for `role`, `is_active`, `department`, or `position`.
- Safeguard:
  - Ensure role migration has run.
  - In drifted DBs, use targeted repair migration before using this page.

### 2) Last super-admin lockout risk
- Symptom: accidental demotion/deletion of the final `super_admin` would remove top-level control.
- Safeguard:
  - Keep explicit guards in update/delete paths.
  - Keep UI delete/demote options hidden when guard conditions fail.

### 3) Self-deactivation/self-deletion
- Symptom: admin disables or deletes own account and loses access mid-session.
- Safeguard:
  - Keep hard server-side checks (not only button hiding).
  - Preserve clear flash error messages.

### 4) Foreign-key delete failure
- Symptom: delete action fails due to related records referencing user.
- Safeguard:
  - Keep `QueryException` catch with clear user-facing message.
  - Consider soft-deletes if hard deletes are too restrictive.

### 5) Authorization bypass in cloned versions
- Symptom: non-admin user can trigger mutation methods via crafted requests.
- Safeguard:
  - Keep `ensureCanManageUsers()` checks in all mutating actions.
  - Keep page-level `mount()` guard as second layer.

### 6) Inconsistent role option visibility
- Symptom: non-super-admin sees or can assign `super_admin`.
- Safeguard:
  - Keep conditional role option rendering in both create and edit forms.
  - Also enforce backend checks before save.

### 7) Duplicate email collisions
- Symptom: create/update validation fails unexpectedly.
- Safeguard:
  - Preserve unique email validation rules.
  - Keep seed/test emails deterministic to avoid accidental duplication.

### 8) Search pagination mismatch
- Symptom: blank page after changing search term on later pages.
- Safeguard:
  - Keep `updatingSearch()` -> `resetPage()` behavior.
  - Preserve `search` query string for consistent navigation state.

## Seeder Behavior for Demo
- `database/seeders/UserRoleSeeder.php` creates representative accounts:
  - 1 super_admin
  - 1 admin
  - 2 managers
  - 3 supervisors
  - 5 operators
- Uses `updateOrCreate` by email for idempotency.
- Also upgrades `test@example.com` to active admin profile.

## Key Validation Rules
- Edit:
  - `editName`: required string max 255
  - `editEmail`: required email unique except current user
  - `editRole`: enum whitelist
- Create:
  - `createName`: required string max 255
  - `createEmail`: required email unique
  - `createPassword`: `Password::defaults()`
  - `createPasswordConfirmation`: same as password
  - `createRole`: enum whitelist

## UX/Styling Notes to Replicate
- Tailwind card design with neutral borders and soft shadows
- Role badges with distinct colors:
  - super_admin red
  - admin purple
  - manager blue
  - supervisor yellow
  - operator gray
- Modal overlay and centered dialog behavior
- Search input debounced: `wire:model.live.debounce.300ms`
- Status/action affordances must respect permission gates in both UI and server methods

## Exact Replica Acceptance Checklist
- Route `/admin/users` exists and loads Livewire component.
- Access denied for non-admin users (403).
- Search works across name/email/department/position.
- Pagination uses 15 rows per page.
- Create modal works and enforces role constraints.
- Edit modal works and enforces last super-admin protection.
- Status toggle blocks own account deactivation.
- Delete blocks own account and last super-admin deletion.
- Role summary cards and role definition panel match structure.
- Flash success/error alerts render correctly after actions.

## Prompt Template for Another AI Agent
Use this to request a faithful clone:

```text
Replicate the Users & Roles page exactly as documented in `MES_Users_Roles_Implementation_Guide.md`.
Use Laravel + Livewire full-page component architecture.
Implement route `/admin/users` with component `AdminUsersRoles`.
Match all CRUD actions, role guards, self-protection rules, last-super-admin protections, and UI behavior (summary cards, table, search, status toggle, create/edit modals, role definitions panel).
Preserve validation rules, pagination (15), and role option visibility based on current user super-admin status.
```

## Diff vs Typical Breeze/Jetstream User Management

This module is intentionally different from standard Breeze/Jetstream profile management. A replica must keep these differences:

- **Admin master list page, not self-profile page**
  - Typical scaffold: user edits own profile.
  - This page: centralized admin table for managing all users.

- **Single-role enum model, not team/permission package UI**
  - Typical scaffold often assumes default auth only, or Spatie permissions if customized.
  - This implementation uses one `users.role` enum (`super_admin`, `admin`, `manager`, `supervisor`, `operator`) plus helper methods on `User`.

- **Operational fields beyond default auth**
  - Uses `is_active`, `department`, `position` as first-class fields in create/edit/search/list.
  - Default Breeze/Jetstream does not include these user-admin fields.

- **Hard business rules around super-admin safety**
  - Prevent demoting/deleting the last `super_admin`.
  - Non-super-admin cannot manage `super_admin` accounts.
  - Prevent self-delete and self-deactivation from this screen.
  - These constraints are stricter than scaffold defaults.

- **Livewire modals and inline actions**
  - Create/edit run inside Livewire modals on the same page.
  - Status toggle and delete are inline row actions with guard checks.
  - Scaffold defaults generally use separate pages/controllers.

- **Role analytics + definitions UI**
  - Includes role distribution cards and role-definition panel.
  - Not part of typical Breeze/Jetstream account pages.

- **Route and intent**
  - Canonical endpoint is `/admin/users` (`admin.users`), owned by admin area navigation.
  - Do not replace with generic `/profile` or Jetstream account settings flow.

