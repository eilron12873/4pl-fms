# Inventory Control – Use Cases (4PL FMS)

This document defines the **Inventory Control** scope in the 4PL Financial Management System. The FMS holds **own inventory only**. Customer stock (custody) is managed in the WMS; the FMS receives only the minimal data needed to bill storage and handling.

---

## Design principle

- **FMS Inventory Control = Own inventory only.** Company-owned stock that appears on the balance sheet. No custody ledger in the FMS.
- **Custody data stay in the WMS.** Quantities, movements, and locations by client are managed and stored in the Warehouse Management System.
- **Billing:** The WMS (or integration layer) sends **billable data** to the FMS (e.g. pallet-days, handling events). The FMS uses this to create AR invoices and post storage/handling revenue. No need for a full custody ledger in the FMS.

---

## 1. Own Inventory (Company Stock) — in FMS

### Definition

**Own inventory** is stock that the company (4PL/3PL) **owns**. It appears on the company’s balance sheet as an asset and is valued at cost (e.g. weighted average).

### Typical examples

- Packaging materials (boxes, pallets, tape)
- Spare parts and consumables
- Fuel or other supplies
- Any goods the company has purchased and holds for its own use or resale

### Accounting treatment

- **Balance sheet:** Recognised as an **inventory asset** (e.g. GL account 12xx or similar).
- **Valuation:** Quantity × unit cost (weighted average); value is used for financial reporting and P&amp;L.
- **Movements:** Receipts (increase asset), issues (reduce asset, may hit COGS or expense).

### What the system does

- **Warehouses** and **Items** (SKUs) define where and what is stored.
- **Movements:** receipt, issue, transfer_in, transfer_out, adjustment, write_off.
- **Balances:** Per warehouse + item: quantity and **unit_cost**; **value** = quantity × unit_cost.
- **Reports:** Valuation report (by warehouse/item), total inventory value, movement history, write-offs and adjustments.

### Who uses it

- Finance: valuation, balance sheet, cost of goods.
- Operations: where company materials are, receipts and issues.

---

## 2. Stock in Custody (Customer Stock) — in WMS, not FMS

### Definition

**Stock in custody** is stock that **customers own** and that the company holds in its warehouses. The company does not own this stock; it provides storage and handling. Revenue comes from fees (storage, handling, etc.), not from selling the goods.

### Where it is managed

- **Custody data (quantities, movements, locations by client)** are held and managed in the **WMS**, not in the FMS.
- The FMS does **not** duplicate custody data or maintain a custody ledger.

### Billing: WMS → FMS feed

The FMS receives **minimal billable data** from the WMS (or integration layer) to calculate and post storage/handling revenue:

- **API:** `POST /api/wms-billing/feed` (authenticated; permission `integration.wms-billing`).
- **Payload (storage):** `client_id`, `event_type: storage-accrual`, `event_date`, `pallet_days`. Creates a draft AR invoice using the client’s storage contract (e.g. per pallet-day rate).
- **Payload (handling):** `client_id`, `event_type: handling-accrual`, `event_date`, `quantity` (e.g. number of movements). Creates a draft AR invoice using the client’s handling contract (e.g. per-trip/movement rate).
- No custody balances or movement history are stored in the FMS; only the billable event is used to create invoice lines.

### Who uses it

- **WMS:** Holds custody data; sends aggregated billable data to FMS.
- **FMS:** Receives feed, creates draft invoices, posts revenue when invoices are issued.

---

## Summary

| Aspect              | Own Inventory (FMS)           | Stock in Custody                |
|---------------------|-------------------------------|----------------------------------|
| **Where**           | FMS Inventory Control         | WMS                              |
| **Owner**           | Company                       | Customer (client)                 |
| **Balance sheet**   | Yes (inventory asset)         | No                               |
| **Valuation**       | Quantity × unit cost          | Not in FMS                       |
| **Billing**         | N/A                           | WMS feeds FMS via API; FMS bills |

The FMS **Inventory Control** menu contains only **own inventory** (valuation, movements, adjustments, warehouses, items). Custody is not part of the FMS; billing is integrated via the WMS billing feed.
