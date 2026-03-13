## LFS Chart of Accounts (COA) Structure and Numbering

System: `LFS` — Logistics Financial System  
Version: `1.0`  
Scope: 3PL / 4PL logistics enterprises (warehousing, transportation, project logistics, value‑added services)

---

## 1. Design Objectives

The LFS COA is designed to:

- **Support logistics operations** (3PL / 4PL, warehousing, transport, project logistics)
- **Enable shipment‑level and client‑level profitability**
- **Stay compatible with standard financial reporting** (BS, P&L, CF)
- **Scale across multiple entities, warehouses, routes, and service lines**

The COA is **hierarchical and expandable**, forming the backbone of:

- Event‑driven accounting (shipment events, warehouse events, project milestones)
- Profitability analytics (by client, shipment, route, warehouse, vehicle, project, service line, cost center)

---

## 2. Numbering Structure

The LFS COA uses a **positional numeric pattern**:

- **Major Category**: `XXXX` (4 digits)  
- **Sub‑Category**: `XX` (2 digits)  
- **Detail Account**: `XX` (2 digits)

Conceptual pattern:

- `XXXX` — Major category  
- `XXXXXX` — Major + subcategory  
- `XXXXXXXX` — Major + subcategory + detail

### 2.1 Example

- `4000` — Revenue (major category)  
- `4100` — Warehousing Revenue (subcategory under Revenue)  
- `4110` — Pallet Storage Revenue (detail under Warehousing Revenue)
- `411001` — Pallet Storage Revenue (implementation code with extra granularity)

This allows:

- Simple reporting at **major** level (e.g. all `4xxx` = Revenue)
- More detail at **sub‑category** and **detail** levels (e.g. `41xx` warehousing vs `42xx` transport)
- Optional **extended numeric codes** for future expansion without breaking the hierarchy

---

## 3. Major Account Groups and Ranges

Top‑level COA groups:

Code | Category
-----|---------
`1000` | Assets
`2000` | Liabilities
`3000` | Equity
`4000` | Revenue
`5000` | Cost of Services
`6000` | Operating Expenses
`7000` | Other Income
`8000` | Other Expenses

Each major group can be expanded into subgroups and detail accounts using the numbering rules.

---

## 4. Assets (`1000` Range)

### 4.1 Current Assets (`1000`–`1499`)

Sample structure:

- `1010` — Cash on Hand  
- `1020` — Bank Account  
- `1030` — Petty Cash  
- `1100` — Accounts Receivable  
  - `1110` — Trade Receivables  
  - `1120` — Unbilled Revenue  
  - `1130` — Accrued Revenue  
- `1200` — Inventory  
  - `1210` — Warehouse Inventory  
  - `1220` — Packaging Materials  
- `1300` — Prepaid Expenses

**4PL application example**

- A client pays in advance for 3 months of pallet storage:
  - **Debit** `1300` Prepaid Expenses  
  - **Credit** `1100` Accounts Receivable / `1020` Bank Account
- When each month’s storage is earned, LFS posts:
  - **Debit** `1300` Prepaid Expenses  
  - **Credit** `4110` Pallet Storage Revenue

### 4.2 Fixed Assets (`1500`–`1599`)

- `1510` — Trucks  
- `1520` — Trailers  
- `1530` — Forklifts  
- `1540` — Containers  
- `1550` — Warehouse Equipment  
- `1560` — IT Equipment

### 4.3 Accumulated Depreciation (`1600`–`1699`)

- `1610` — Accumulated Depreciation — Trucks  
- `1620` — Accumulated Depreciation — Equipment

**4PL application example**

- New delivery truck acquired:
  - **Debit** `1510` Trucks  
  - **Credit** `2210` Bank Loans / `2010` Accounts Payable
- Monthly depreciation:
  - **Debit** `6000` Operating Expenses (Depreciation subaccount)  
  - **Credit** `1610` Accumulated Depreciation — Trucks

---

## 5. Liabilities (`2000` Range)

### 5.1 Current Liabilities (`2000`–`2199`)

- `2010` — Accounts Payable  
- `2020` — Vendor Payables  
- `2030` — Accrued Expenses  
  - Includes accrued freight costs, fuel costs, other logistics‑related accruals  
- `2040` — Accrued Freight Cost  
- `2050` — Accrued Fuel Cost  
- `2060` — Payroll Payable  
- `2070` — Taxes Payable

### 5.2 Long‑Term Liabilities (`2200`–`2299`)

- `2210` — Bank Loans  
- `2220` — Lease Liabilities

**4PL application example**

- At month‑end, fuel and subcontracted freight for shipments are not yet invoiced:
  - **Debit** `5100` Transport Costs (or detail accounts like `5110` Fuel, `5130` Subcontracted Freight)  
  - **Credit** `2040` Accrued Freight Cost / `2050` Accrued Fuel Cost
- When vendor invoices are received:
  - **Debit** `2040` / `2050` Accrued accounts  
  - **Credit** `2010` Accounts Payable

---

## 6. Equity (`3000` Range)

- `3000` — Share Capital  
- `3100` — Retained Earnings  
- `3200` — Current Year Earnings

These accounts are used for ownership structure and periodic closing of profits and losses.

---

## 7. Revenue (`4000` Range)

Revenue is structured around **logistics service lines** to support shipment‑level profitability.

### 7.1 Logistics Revenue (`4000`–`4499`)

#### Warehousing Revenue (`4100`–`4199`)

- `4100` — Warehousing Revenue  
- `4110` — Pallet Storage Revenue  
- `4120` — Handling Revenue  
- `4130` — Pick & Pack Revenue

#### Transportation Revenue (`4200`–`4299`)

- `4200` — Transportation Revenue  
- `4210` — Domestic Transport Revenue  
- `4220` — International Freight Revenue  
- `4230` — Courier Revenue

#### Project Logistics Revenue (`4300`–`4399`)

- `4300` — Project Logistics Revenue  
- `4310` — Project Cargo Revenue  
- `4320` — Special Handling Revenue

#### Value Added Services (`4400`–`4499`)

- `4400` — Value Added Services  
- `4410` — Labeling Revenue  
- `4420` — Packaging Revenue

**4PL application examples**

1. **Domestic FTL shipment (door to door)**  
   - Client books a domestic full‑truckload delivery. At delivery completion:
     - **Debit** `1100` Accounts Receivable  
     - **Credit** `4210` Domestic Transport Revenue  
     - Dimensions on journal line: `client_id`, `shipment_id`, `route_id`, `vehicle_id`, `service_line = domestic_transport`

2. **Warehouse storage and handling**  
   - Monthly pallet storage + inbound handling:
     - **Debit** `1100` Accounts Receivable  
     - **Credit** `4110` Pallet Storage Revenue  
     - **Credit** `4120` Handling Revenue  
     - Dimensions: `client_id`, `warehouse_id`, `service_line = warehousing`

3. **Project logistics move**  
   - Heavy‑lift project with special rigging:
     - **Debit** `1100` Accounts Receivable  
     - **Credit** `4310` Project Cargo Revenue  
     - **Credit** `4320` Special Handling Revenue  
     - Dimensions: `client_id`, `project_id`, `route_id`, `service_line = project_logistics`

---

## 8. Cost of Services (`5000` Range)

Cost of services are **direct logistics costs** attributable to shipments, warehousing operations, and handling.

### 8.1 Direct Logistics Costs (`5000`–`5399`)

#### Transport Costs (`5100`–`5199`)

- `5100` — Transport Costs  
- `5110` — Fuel Expense  
- `5120` — Toll Fees  
- `5130` — Subcontracted Freight

#### Warehouse Operating Costs (`5200`–`5299`)

- `5200` — Warehouse Operating Costs  
- `5210` — Warehouse Labor  
- `5220` — Forklift Operations  
- `5230` — Warehouse Utilities

#### Handling Costs (`5300`–`5399`)

- `5300` — Handling Costs  
- `5310` — Packaging Materials  
- `5320` — Handling Labor

**4PL application examples**

1. **Linehaul fuel and tolls for a shipment**
   - Fuel and tolls incurred for a specific shipment:
     - **Debit** `5110` Fuel Expense  
     - **Debit** `5120` Toll Fees  
     - **Credit** `2010` Accounts Payable / `1020` Bank Account  
     - Dimensions: `shipment_id`, `route_id`, `vehicle_id`, `service_line = transport`

2. **Warehouse labor for pallet moves**
   - Dedicated warehouse crew handling inbound and outbound pallets:
     - **Debit** `5210` Warehouse Labor  
     - **Credit** `2060` Payroll Payable  
     - Dimensions: `warehouse_id`, `service_line = warehousing`, `cost_center = warehouse_ops`

3. **Packaging materials for e‑commerce orders**
   - Cartons, bubble wrap, and labels:
     - **Debit** `5310` Packaging Materials  
     - **Credit** `2010` Accounts Payable  
     - Dimensions: `client_id`, `warehouse_id`, `service_line = value_added_services`

---

## 9. Operating Expenses (`6000` Range)

Operating expenses capture **administrative and overhead costs** not directly attributable as cost of services.

### 9.1 Administrative Expenses (`6000`–`6499`)

- `6100` — Salaries and Wages  
  - `6110` — Office Salaries  
  - `6120` — Management Salaries  
- `6200` — Office Expenses  
  - `6210` — Office Supplies  
  - `6220` — Internet and Communication  
- `6300` — IT Expenses  
  - `6310` — Software Subscriptions  
  - `6320` — System Maintenance  
- `6400` — Marketing Expenses  
  - `6410` — Advertising  
  - `6420` — Business Development

**4PL application examples**

1. **Back‑office and management salaries**
   - **Debit** `6100` Salaries and Wages (with subaccounts for office vs management)  
   - **Credit** `2060` Payroll Payable  
   - Dimensions: `cost_center = head_office`, `service_line` as applicable if you allocate by driver

2. **TMS / WMS subscription**
   - **Debit** `6310` Software Subscriptions  
   - **Credit** `2010` Accounts Payable  
   - Dimensions: `cost_center = IT`, `service_line = shared`

3. **Marketing spend for new 4PL solution**
   - **Debit** `6410` Advertising  
   - **Credit** `2010` Accounts Payable  
   - Dimensions: `project_id = marketing_campaign`, `service_line = 4pl_solutions`

---

## 10. Other Income and Expenses (`7000`–`8999`)

### 10.1 Other Income (`7000`–`7099`)

- `7010` — Interest Income  
- `7020` — Miscellaneous Income

### 10.2 Other Expenses (`8000`–`8099`)

- `8010` — Interest Expense  
- `8020` — Penalties and Fines  
- `8030` — Loss on Asset Disposal

**4PL application examples**

- Bank interest earned on operating accounts:
  - **Debit** `1020` Bank Account  
  - **Credit** `7010` Interest Income
- Late payment penalties from tax authorities:
  - **Debit** `8020` Penalties and Fines  
  - **Credit** `2070` Taxes Payable

---

## 11. Dimensions and COA Interaction

The COA is intentionally **kept clean and service‑oriented**, while operational detail is carried through **dimensions on each journal line**:

- `client_id`  
- `shipment_id`  
- `route_id`  
- `warehouse_id`  
- `vehicle_id`  
- `project_id`  
- `service_line`  
- `cost_center`

**Example: Shipment revenue posting**

- Account: `4210` Domestic Transport Revenue  
- Dimensions:
  - `client_id = ACME_IMPORTS`
  - `shipment_id = SHP123456`
  - `route_id = MNL‑CEB`
  - `vehicle_id = TRK‑001`
  - `service_line = domestic_transport`

This design:

- Keeps the COA **manageable and standardized**
- Enables **rich profitability analysis** by client, shipment, route, vehicle, warehouse, and project
- Avoids creating hundreds of client‑specific or lane‑specific GL accounts

---

## 12. Practical Usage Guidelines for 3PL / 4PL Clients

- **Use LFS COA codes as the master** and map legacy COA codes into LFS accounts.
- **Separate service lines by account groups**:
  - Warehousing: `4100` / `5200` / `5210` / `5230`
  - Transportation: `4200` / `5100` / `5110` / `5120` / `5130`
  - Project Logistics: `4300` / `4310` / `4320` / `5300`–`5320`
  - Value Added Services: `4400` / `4410` / `4420` / `5310`
- **Use dimensions, not extra GL codes**, to represent:
  - Clients, routes, lanes, vehicles
  - Warehouses and projects
  - Cost centers and business units

This structure and numbering system gives a **standardized, logistics‑ready COA** that is easy to implement, extend, and map from existing systems such as QuickBooks, while unlocking detailed 3PL / 4PL profitability analytics in LFS.

