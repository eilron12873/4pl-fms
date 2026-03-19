# COA Governance Standard v1

System: `LFS`  
Module: `Core Accounting`  
Version: `1.0`  
Status: `Frozen baseline for production-readiness phases`

---

## 1. Purpose

This standard defines mandatory governance for the LFS Chart of Accounts (COA) to prevent structural drift across modules, posting rules, and integrations.

---

## 2. Canonical Coding Model

LFS uses **6-digit `XYYZZZ`** account codes.

- `X` = financial statement class
  - `1` assets
  - `2` liabilities
  - `3` equity
  - `4` revenue
  - `5` cost of services
  - `6` operating expenses
  - `7` other income
  - `8` other expenses
- `YY` = category
- `ZZZ` = detail / subgroup

Major roots:

- `100000`, `200000`, `300000`, `400000`, `500000`, `600000`, `700000`, `800000`

---

## 3. Parent Logic and Levels

Parent derivation is based on trailing-zero hierarchy:

- `X00000` -> level 1 (no parent)
- `XY0000` -> parent `X00000`
- `XYY000` -> parent `XY0000`
- `XYYZ00` -> parent `XYY000`
- `XYYZZ0` -> parent `XYYZ00`
- `XYYZZZ` -> parent `XYYZZ0` when present

This parent logic must remain deterministic and consistent in seeding, import, and UI editing.

---

## 4. Posting Policy

- **Group nodes** with children are **non-posting** (`is_posting = false`).
- **Leaf/detail nodes** are **posting** (`is_posting = true`).
- If a parent was previously posting and child detail accounts are introduced, parent must be switched to non-posting.

Examples:

- `152700` Vehicles -> non-posting group
- `152710` Trucks (Logistics) -> posting detail
- `531000` Fuel Expense -> non-posting group (when `5311xx` children are used)
- `531100` Fuel Expense (LFS Detail) -> posting detail

---

## 5. Change-Control Rules

All COA changes must follow controlled workflow:

1. Propose change with business reason, impacted modules, and migration impact.
2. Verify parent/level/posting consistency.
3. Update mapping impacts:
   - posting rules
   - handler defaults
   - import templates
   - reports
4. Capture audit record:
   - who changed
   - before/after
   - approval reference
5. Publish release notes for downstream module owners.

Forbidden without approved migration:

- ad-hoc code renumbering
- reusing retired codes for different meaning
- making group nodes posting while detailed children exist

---

## 6. Legacy Mapping Governance

For migration from legacy 4-digit charts:

- maintain explicit `legacy_code -> lfs_code` mapping
- preserve historical lineage in migration artifacts
- do not delete mapping history after cutover
- apply reconciliation controls at trial balance and control-account levels

Minimum migration fields:

- legacy_code
- legacy_name
- lfs_code
- lfs_name
- mapping_type (`one_to_one`, `many_to_one`, `split`)
- effective_date
- migration_batch

---

## 7. Ownership

- **Finance Architecture Owner**: approves accounting structure intent.
- **Core Accounting Engineering Owner**: enforces implementation consistency.
- **Audit/Compliance Owner**: validates traceability and change evidence.

No COA changes move to production without joint sign-off.

