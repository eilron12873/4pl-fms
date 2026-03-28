# Audit & Governance – Environment Settings Guide

This guide documents environment variables that control **audit log CSV export** and **optional retention pruning**. They are read by [`config/audit.php`](../../config/audit.php) in the Laravel application.

---

## Quick reference

| Variable | Config key | Default (if unset) | Purpose |
|----------|------------|--------------------|---------|
| `AUDIT_EXPORT_MAX_ROWS` | `audit.export.max_rows` | `10000` | Hard cap on rows written per CSV export |
| `AUDIT_EXPORT_MAX_DATE_SPAN_DAYS` | `audit.export.max_date_span_days` | `366` | Maximum allowed **from_date → to_date** span when exporting |
| `AUDIT_EXPORT_REQUIRE_DATE_RANGE` | `audit.export.require_date_range` | `true` | If `true`, export requires both **from** and **to** dates |
| `AUDIT_PRUNE_ENABLED` | `audit.prune.enabled` | `false` | If `true`, `audit:prune-activities` may **delete** old rows |
| `AUDIT_RETENTION_DAYS` | `audit.prune.default_retention_days` | `2555` | Age cutoff (~7 years) when pruning runs |

---

## Where to set values

1. Open your **`.env`** file in the project root (same directory as `composer.json`).
2. Add or edit the lines below (no quotes needed for numbers; use `true` / `false` for booleans).
3. After changing `.env` in production, refresh config cache:

   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

   In local development you can use `php artisan config:clear` only.

---

## Variable details

### `AUDIT_EXPORT_MAX_ROWS`

- **What it does:** Stops the CSV export after this many activity rows, even if more match the filters.
- **Why it matters:** Protects PHP memory and request time on large datasets.
- **Typical values:**
  - **Default / most apps:** `10000`
  - **Larger exports (after testing timeouts):** `25000`–`50000`
  - **Tighter limit:** `5000`

### `AUDIT_EXPORT_MAX_DATE_SPAN_DAYS`

- **What it does:** When both export dates are set, the number of calendar days in the range (inclusive) must not exceed this value.
- **Why it matters:** Prevents “year-long dump” requests from overloading the server unless you allow it.
- **Typical values:**
  - **Full fiscal year + leap day:** `366` (default)
  - **Quarterly-style exports:** `120`–`180`
  - **Monthly-only policy:** `31`–`90`

### `AUDIT_EXPORT_REQUIRE_DATE_RANGE`

- **What it does:** If `true`, the **Export CSV** action requires **From date** and **To date** in the audit log filters.
- **Why it matters:** Avoids exporting an unbounded slice of the table by mistake.
- **Typical values:**
  - **Production:** `true`
  - **Local development only:** `false` (more convenient; use with care)

Accepted truthy values: `true`, `1`, `yes`, `on` (via PHP `FILTER_VALIDATE_BOOL`).

### `AUDIT_PRUNE_ENABLED`

- **What it does:** Gates the [`audit:prune-activities`](../../app/Console/Commands/AuditPruneActivities.php) command. If `false`, the command **does not delete** anything (it exits with a message).
- **Why it matters:** Many organizations **must not** delete audit trails from the application database without legal/ops sign-off; archiving to cold storage is often preferred.
- **Typical values:**
  - **Default / safest:** `false`
  - **Only if policy allows deletion:** `true`

### `AUDIT_RETENTION_DAYS`

- **What it does:** When pruning runs, rows with `created_at` older than **now minus this many days** are candidates for deletion.
- **Why it matters:** Aligns technical retention with **tax, statutory, or internal policy** (often multi-year).
- **Typical values:**
  - **~7 years:** `2555` (default)
  - Adjust only after confirming required retention with finance/legal.

You can override retention for a single run:

```bash
php artisan audit:prune-activities --days=1825
```

Dry run (no deletes):

```bash
php artisan audit:prune-activities --dry-run
```

---

## Copy-paste examples

### Recommended production starter

```env
AUDIT_EXPORT_MAX_ROWS=10000
AUDIT_EXPORT_MAX_DATE_SPAN_DAYS=366
AUDIT_EXPORT_REQUIRE_DATE_RANGE=true
AUDIT_PRUNE_ENABLED=false
AUDIT_RETENTION_DAYS=2555
```

### Local development (relaxed export only)

```env
AUDIT_EXPORT_MAX_ROWS=10000
AUDIT_EXPORT_MAX_DATE_SPAN_DAYS=366
AUDIT_EXPORT_REQUIRE_DATE_RANGE=false
AUDIT_PRUNE_ENABLED=false
AUDIT_RETENTION_DAYS=2555
```

### Pruning enabled (only after explicit approval)

```env
AUDIT_PRUNE_ENABLED=true
AUDIT_RETENTION_DAYS=2555
```

Keep export settings as in production unless you have a reason to change them.

---

## Related UI and code

- **Audit log screen:** LFS Administration → Audit Logs (`/lfs-administration/audit-logs`)
- **Export route:** `lfs-administration.audit-logs.export` (same permission as viewing audit logs)
- **Config file:** `config/audit.php`
- **Module spec:** [Audit_Governance_Module_Documentation.md](./Audit_Governance_Module_Documentation.md)

---

## Checklist before going live

- [ ] `AUDIT_EXPORT_REQUIRE_DATE_RANGE=true` in production.
- [ ] `AUDIT_PRUNE_ENABLED=false` unless deletion is approved and documented.
- [ ] `AUDIT_RETENTION_DAYS` matches your retention policy if pruning is ever enabled.
- [ ] Run a test export in staging with realistic data volume and confirm no gateway/PHP timeouts.
- [ ] Run `php artisan config:cache` on production after `.env` changes.
