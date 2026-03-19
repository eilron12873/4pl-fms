## LFS Chart of Accounts (COA) Structure and Numbering

System: `LFS` — Logistics Financial System  
Version: `2.0`  
Scope: 3PL / 4PL logistics enterprises (warehousing, transportation, project logistics, value-added services)

---

## 1. Design Objectives

The LFS COA is designed to:

- Support logistics operations with standardized account coding.
- Enable shipment-level and client-level profitability.
- Align reporting across entities and business units.
- Support statutory and tax extensions without code redesign.

---

## 2. Numbering Structure (Current Standard)

LFS now uses a **6-digit `XYYZZZ` coding model**.

- `X` = Financial statement class
  - `1` Assets
  - `2` Liabilities
  - `3` Equity
  - `4` Revenue
  - `5` Cost of Services
  - `6` Operating Expenses
  - `7` Other Income
  - `8` Other Expenses
- `YY` = Category
- `ZZZ` = Detailed account or subgroup

### 2.1 Parent and Level Logic

Parent logic follows trailing-zero hierarchy:

- `100000` Assets -> no parent (Level 1)
- `110000` Current Assets -> parent `100000` (Level 2)
- `111000` Cash and Cash Equivalents -> parent `110000` (Level 3)
- `111100` Cash on Hand -> parent `111000` (Level 4)
- `111110` (optional deeper detail) -> parent `111100` (Level 5)

Rule summary:

- `X00000` = Level 1 class root
- `XYY000` / `XY0000` families = grouping levels
- Non-zero detail suffixes = posting/detail levels

---

## 3. Major Account Groups

Code | Category
-----|---------
`100000` | Assets
`200000` | Liabilities
`300000` | Equity
`400000` | Revenue
`500000` | Cost of Services
`600000` | Operating Expenses
`700000` | Other Income
`800000` | Other Expenses

---

## 4. Asset Structure Example (`1xxxxx`)

- `100000` Assets
  - `110000` Current Assets
    - `111000` Cash and Cash Equivalents
      - `111100` Cash on Hand
      - `111200` Petty Cash Fund
    - `112000` Cash in Bank
      - `112100` Cash in Bank - BDO
    - `120000` Receivables
      - `121000` Accounts Receivable
        - `121100` Trade Receivables
  - `130000` Inventory
    - `131000` Merchandise Inventory
  - `150000` Non-Current Assets
    - `152000` Property Plant and Equipment
      - `152700` Vehicles (non-posting group)
        - `152710` Trucks (Logistics)
        - `152720` Trailers (Logistics)
      - `152800` IT Equipment (non-posting group)
        - `152810` IT Equipment (Logistics)
      - `152900` Warehouse Equipment (non-posting group)
        - `152910` Warehouse Equipment (Logistics)

Important: group nodes (such as `152700`, `152800`, `152900`) should be **non-posting** when deeper child detail accounts are used.

---

## 5. Revenue Structure Example (`4xxxxx`)

- `400000` Revenue
  - `410000` Service Revenue
    - `411000` Warehousing Revenue
    - `412000` Storage Revenue
    - `413000` Handling Revenue
    - `414000` Distribution Revenue
  - `420000` Transport Revenue
    - `421000` Trucking Revenue
    - `422000` Delivery Revenue
    - `423000` Freight Revenue
    - `424000` Container Handling Revenue
  - `430000` Other Service Revenue
    - `431000` Consultation Revenue
    - `432000` Installation Revenue
    - `433000` Maintenance Revenue

---

## 6. Cost of Services Example (`5xxxxx`)

- `500000` Cost of Services
  - `510000` Direct Labor
    - `511000` Warehouse Labor (non-posting when child detail exists)
      - `511100` Handling Labor (LFS Detail)
  - `520000` Handling Cost
    - `521000` Equipment Operations
  - `530000` Transport Cost
    - `531000` Fuel Expense (non-posting when child detail exists)
      - `531100` Fuel Expense (LFS Detail)
      - `531200` Subcontracted Freight (LFS Detail)
    - `533000` Toll Fees (non-posting when child detail exists)
      - `533100` Toll Fees (LFS Detail)

This ensures detail accounts are grouped correctly and posting happens at leaf nodes.

---

## 7. 4PL Journal Examples (6-digit)

1. Shipment delivered (transport revenue):
   - Debit `121100` Trade Receivables
   - Credit `423000` Freight Revenue

2. Vendor transport invoice:
   - Debit `530000` Transport Cost (or detail child such as `531100`)
   - Credit `211100` Trade Payables

3. Fixed asset purchase (equipment):
   - Debit `152500` Equipment
   - Credit `211100` Trade Payables

4. Depreciation:
   - Debit `651000` Depreciation Expense
   - Credit `153300` Accumulated Depreciation - Equipment

---

## 8. Dimensions and COA Interaction

Dimensions continue at journal-line level:

- `client_id`
- `shipment_id`
- `route_id`
- `warehouse_id`
- `vehicle_id`
- `project_id`
- `service_line`
- `cost_center`

Use dimensions for operational granularity and keep the COA structurally clean.

---

## 9. Migration Guidance

For clients migrating from QuickBooks or legacy 4-digit charts:

- Keep LFS 6-digit `XYYZZZ` as canonical codes.
- Map legacy accounts to LFS codes and dimensions.
- Avoid adding client-specific or branch-specific GL duplicates; use dimensions.
- Ensure all posting rules and defaults use 6-digit codes only.

This standard supports consistent analytics, cleaner hierarchy, and scalable multi-client onboarding.

