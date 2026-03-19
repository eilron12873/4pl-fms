# Posting Rule Governance v1

Versioning and governance are now implemented with:

- `posting_rule_versions` table
- `posting_rule_audit_logs` table
- lifecycle service (`draft -> review -> approved -> active -> retired`)
- automatic version + audit snapshots on create/update in posting rule UI flow

## Operational rules

- Any material rule change creates a new version record.
- Rule state changes must follow lifecycle transitions.
- Active versions should carry `effective_from` date.
- Audit logs store immutable before/after snapshots and actor metadata.

## Database artifacts

- `app/Modules/CoreAccounting/migrations/2026_03_19_090000_create_posting_rule_versions_table.php`
- `app/Modules/CoreAccounting/migrations/2026_03_19_090100_create_posting_rule_audit_logs_table.php`

