# LFS Chart of Accounts Master Design

## Logistics Enterprise COA (Chart of Accounts)

System: LFS -- Logistics Financial System\
Version: 1.0\
Purpose: Enterprise Chart of Accounts optimized for 3PL / 4PL Logistics
Operations

------------------------------------------------------------------------

# 1. Objective

This document defines the **Master Chart of Accounts (COA)** for the LFS
platform.

The design supports:

• Event-driven accounting\
• Logistics operational integration\
• Shipment-level profitability analysis\
• Enterprise financial reporting\
• Multi-service logistics providers

The structure is optimized for:

-   3PL logistics providers
-   4PL logistics orchestrators
-   Warehousing operators
-   Freight forwarding companies
-   Integrated logistics enterprises

------------------------------------------------------------------------

# 2. COA Design Principles

The LFS COA follows the principles:

1.  Hierarchical structure
2.  Expandable numbering scheme
3.  Service-line revenue segmentation
4.  Cost visibility by logistics activity
5.  Profitability analytics readiness
6.  Compliance with standard financial reporting

------------------------------------------------------------------------

# 3. Account Number Structure

Recommended structure:

XXXX -- Major category\
XX -- Sub category\
XX -- Detail account

Example:

4000 -- Revenue\
4100 -- Warehousing Revenue\
4110 -- Pallet Storage Revenue

Example code:

411001 -- Pallet Storage Revenue

------------------------------------------------------------------------

# 4. Major Account Groups

  Code   Category
  ------ --------------------
  1000   Assets
  2000   Liabilities
  3000   Equity
  4000   Revenue
  5000   Cost of Services
  6000   Operating Expenses
  7000   Other Income
  8000   Other Expenses

------------------------------------------------------------------------

# 5. Assets

## 1000 Current Assets

1010 Cash on Hand\
1020 Bank Account\
1030 Petty Cash\
1100 Accounts Receivable\
1110 Trade Receivables\
1120 Unbilled Revenue\
1130 Accrued Revenue\
1200 Inventory\
1210 Warehouse Inventory\
1220 Packaging Materials\
1300 Prepaid Expenses

------------------------------------------------------------------------

## 1500 Fixed Assets

1510 Trucks\
1520 Trailers\
1530 Forklifts\
1540 Containers\
1550 Warehouse Equipment\
1560 IT Equipment

------------------------------------------------------------------------

## 1600 Accumulated Depreciation

1610 Accumulated Depreciation -- Trucks\
1620 Accumulated Depreciation -- Equipment

------------------------------------------------------------------------

# 6. Liabilities

## 2000 Current Liabilities

2010 Accounts Payable\
2020 Vendor Payables\
2030 Accrued Expenses\
2040 Accrued Freight Cost\
2050 Accrued Fuel Cost\
2060 Payroll Payable\
2070 Taxes Payable

------------------------------------------------------------------------

## 2200 Long-Term Liabilities

2210 Bank Loans\
2220 Lease Liabilities

------------------------------------------------------------------------

# 7. Equity

3000 Share Capital\
3100 Retained Earnings\
3200 Current Year Earnings

------------------------------------------------------------------------

# 8. Revenue Accounts

## 4000 Logistics Revenue

4100 Warehousing Revenue\
4110 Pallet Storage Revenue\
4120 Handling Revenue\
4130 Pick & Pack Revenue

4200 Transportation Revenue\
4210 Domestic Transport Revenue\
4220 International Freight Revenue\
4230 Courier Revenue

4300 Project Logistics Revenue\
4310 Project Cargo Revenue\
4320 Special Handling Revenue

4400 Value Added Services\
4410 Labeling Revenue\
4420 Packaging Revenue

------------------------------------------------------------------------

# 9. Cost of Services

## 5000 Direct Logistics Costs

5100 Transport Costs\
5110 Fuel Expense\
5120 Toll Fees\
5130 Subcontracted Freight

5200 Warehouse Operating Costs\
5210 Warehouse Labor\
5220 Forklift Operations\
5230 Warehouse Utilities

5300 Handling Costs\
5310 Packaging Materials\
5320 Handling Labor

------------------------------------------------------------------------

# 10. Operating Expenses

## 6000 Administrative Expenses

6100 Salaries and Wages\
6110 Office Salaries\
6120 Management Salaries

6200 Office Expenses\
6210 Office Supplies\
6220 Internet and Communication

6300 IT Expenses\
6310 Software Subscriptions\
6320 System Maintenance

6400 Marketing Expenses\
6410 Advertising\
6420 Business Development

------------------------------------------------------------------------

# 11. Other Income

7010 Interest Income\
7020 Miscellaneous Income

------------------------------------------------------------------------

# 12. Other Expenses

8010 Interest Expense\
8020 Penalties and Fines\
8030 Loss on Asset Disposal

------------------------------------------------------------------------

# 13. Dimension Compatibility

The COA is designed to integrate with LFS profitability dimensions:

client_id\
shipment_id\
route_id\
warehouse_id\
vehicle_id\
project_id\
service_line\
cost_center

These dimensions are applied at **journal line level**.

------------------------------------------------------------------------

# 14. Example Journal Posting

Shipment Delivered -- Revenue

Debit:

Accounts Receivable (1100)

Credit:

Transport Revenue (4210)

Dimensions:

client_id\
shipment_id\
route_id

------------------------------------------------------------------------

# 15. Financial Reporting Compatibility

The COA supports automatic generation of:

• Trial Balance\
• Income Statement\
• Balance Sheet\
• Cash Flow Statement\
• Profitability Reports

------------------------------------------------------------------------

# 16. Scalability

The numbering scheme supports expansion to:

-   500+ accounts
-   multi-service logistics operations
-   multi-entity consolidation

------------------------------------------------------------------------

# 17. Governance

Finance administrators control:

-   account creation
-   account activation/deactivation
-   mapping to posting rules

Audit logs must capture all COA changes.

------------------------------------------------------------------------

# 18. Implementation Guidance

Seed the Chart of Accounts using:

ChartOfAccountsSeeder

Accounts should include:

account_code\
account_name\
account_type\
parent_account_id\
is_posting_account

------------------------------------------------------------------------

# 19. Future Enhancements

Future LFS releases may extend the COA to include:

-   multi-country tax structures
-   intercompany accounts
-   consolidation accounts
-   advanced cost accounting

------------------------------------------------------------------------

END OF DOCUMENT
