# Legacy to 6-Digit COA Migration Policy v1

System: `LFS`  
Module: `Core Accounting`  
Version: `1.0`

---

## 1. Scope

This policy governs migration from legacy COA structures (including 4-digit and external client charts) to LFS 6-digit `XYYZZZ` codes.

---

## 2. Migration Principles

1. LFS 6-digit code is the canonical destination.
2. Legacy meaning is preserved via mapping history.
3. No silent code remapping in production.
4. Reconciliation sign-off is mandatory before cutover.

---

## 3. Required Mapping Artifacts

Each migration must include:

- Mapping file (`csv/xlsx`) with:
  - `legacy_code`
  - `legacy_name`
  - `lfs_code`
  - `lfs_name`
  - `mapping_type` (`one_to_one`, `many_to_one`, `split`)
  - `notes`
- Validation report:
  - unmapped accounts
  - duplicate target mappings
  - parent/group violations
- Reconciliation report:
  - trial balance parity
  - control accounts parity (AR/AP/Inventory/FA)
  - variance explanations

---

## 4. Cutover Steps

1. Freeze legacy posting window.
2. Export legacy balances.
3. Validate mapping completeness.
4. Load mapped opening balances into LFS.
5. Run reconciliation and variance triage.
6. Obtain finance sign-off.
7. Enable live posting in LFS.

---

## 5. Rollback Requirements

Every migration must define:

- rollback owner
- rollback script/procedure
- maximum rollback window
- communication protocol to finance and operations

---

## 6. Compliance and Audit

Store these records for each migration batch:

- mapping file hash/version
- approved-by and approval date
- cutover execution log
- reconciliation sign-off pack
- post-cutover variance register

