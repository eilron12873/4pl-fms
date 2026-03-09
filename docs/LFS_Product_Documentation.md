# LFS -- Logistics Financial System

## Enterprise Product Architecture & Functional Specification

Version 1.0 Author: Product Architecture Team

------------------------------------------------------------------------

# 1. PRODUCT OVERVIEW

## 1.1 Vision

LFS (Logistics Financial System) is a single-tenant, enterprise-grade
financial control system designed specifically for 4PL and 3PL logistics
providers.

It serves as the Financial Brain of a Logistics Enterprise, tightly
integrated with:

-   WMS -- Warehouse Management System
-   LMS -- Logistics Management System

LFS automates financial operations derived from logistics events
including warehousing, freight, courier, project cargo, subcontracted
transport, and door-to-door delivery.

------------------------------------------------------------------------

# 2. SYSTEM ARCHITECTURE

## 2.1 Deployment Model

-   Single-tenant per client
-   Dedicated server
-   Dedicated database
-   Independent deployment
-   API-based integration with WMS/LMS

## 2.2 High-Level Architecture

WMS/LMS (Operational Systems) \| \| REST API / Event Trigger v LFS
(Financial Engine) \| v Financial Database (Isolated)

## 2.3 Design Principles

-   Event-driven financial automation
-   Immutable ledger enforcement
-   Double-entry accounting compliance
-   Modular architecture
-   Service-oriented design
-   Strict audit logging
-   Period locking control

------------------------------------------------------------------------

# 3. CORE MODULES

## 3.1 Core Accounting Engine

### Features:

-   Hierarchical Chart of Accounts
-   Double-entry Journal Engine
-   Immutable Journal Entries
-   Reversal-only correction policy
-   Period locking
-   Cost center tagging
-   Service line tagging

### Reports:

-   Trial Balance
-   General Ledger
-   Income Statement (by branch/service line)
-   Balance Sheet
-   Cash Flow Statement

------------------------------------------------------------------------

## 3.2 Accounts Receivable (AR)

### Contract-Based Billing Engine

Supports: - Per pallet/day - Per CBM - Per KG - Per trip - Per route -
Per container - Tiered pricing - SLA penalties - Effective date pricing

### Billing Triggers (Event-Based)

Triggered by: - Shipment Delivered - POD Confirmed - Storage Day
Elapsed - ASN Received - Project Milestone Completed

### AR Functionalities

-   Invoice generation (consolidated or per transaction)
-   Credit/Debit Notes
-   Statement of Account
-   AR Aging
-   Collection tracking
-   DSO reporting
-   Manual AR entry: create and edit draft invoices, then issue when approved

------------------------------------------------------------------------

## 3.3 Accounts Payable (AP)

Supports:

-   Subcontracted carriers
-   Air freight partners
-   Sea freight providers
-   Customs brokers
-   Warehouse partners

Features:

-   Manual vendor bill entry (draft bills with editable header/lines, then issue)
-   Accrual posting
-   Accrual reversal
-   AP Aging
-   Payment scheduling
-   Multi-currency support
-   Margin variance alerts
-   AP payment vouchers and check register (check printing with amount-in-words, void workflow)
-   PO-linked AP bills (create bill from approved/received P.O. and link for matching)

------------------------------------------------------------------------

## 3.4 Costing & Profitability Engine

### Profitability Views

-   Per shipment
-   Per client
-   Per route
-   Per warehouse
-   Per vehicle
-   Per project
-   Per service line

### Cost Allocation Engine

Supports:

-   Revenue proportion allocation
-   Volume-based allocation
-   Manual allocation (approval required)

Cost components tracked:

-   Fuel
-   Toll
-   Labor
-   Equipment
-   Subcontractor
-   Storage
-   Handling
-   Overhead

------------------------------------------------------------------------

## 3.5 Inventory Financial Control

-   FIFO valuation
-   Moving average costing
-   Batch/Lot support
-   Write-offs
-   Shrinkage handling
-   Automatic journal posting on stock movement

------------------------------------------------------------------------

## 3.6 Fixed Asset Management

Tracks:

-   Trucks
-   Trailers
-   Containers
-   Forklifts
-   Equipment

Features:

-   Depreciation calculation
-   Maintenance cost integration
-   Cost per KM analysis
-   Asset profitability reporting

------------------------------------------------------------------------

## 3.7 Procurement (Purchase Requests & Purchase Orders)

### Purchase Requests (P.R.)

-   Create purchase requests with multi-line items (description, quantity, estimated cost, account)
-   Status workflow: Draft → Submitted → Approved (with approval date)
-   Visibility into requested-by, department, and notes for audit trail

### Purchase Orders (P.O.)

-   Create purchase orders from an approved P.R. or directly (standalone P.O.)
-   Vendor, currency, order/expected dates, and total amount tracking
-   Status workflow: Draft → Issued → Received (with received date)
-   Line-level quantities, unit prices, and account coding

### Integration with Accounts Payable

-   “Create bill from P.O.” flow in AP to generate a draft vendor bill from an issued/received P.O.
-   AP bill lines pre-filled from the P.O. lines (description and amounts)
-   AP bills keep a link back to the originating P.O. for basic 3-way matching and audit

------------------------------------------------------------------------

# 4. INTEGRATION DESIGN

## 4.1 API Communication

WMS/LMS sends financial events:

POST /api/financial-events/{event_type}

Event Examples:

-   shipment_delivered
-   storage_accrual
-   vendor_invoice_approved
-   project_milestone_completed

LFS validates and posts corresponding journals.

## 4.2 Security Controls

-   JWT/Sanctum authentication
-   Server-to-server token validation
-   IP whitelisting
-   Idempotency key validation
-   Duplicate prevention controls

------------------------------------------------------------------------

# 5. FINANCIAL CONTROLS & GOVERNANCE

-   Immutable ledger entries
-   Journal approval workflow
-   Period locking
-   Audit trail (before/after tracking)
-   Role-based financial permissions
-   Transaction-level DB enforcement

------------------------------------------------------------------------

# 6. NON-FUNCTIONAL REQUIREMENTS

-   ACID-compliant database operations
-   Queue-based event processing
-   Background job handling
-   High availability support
-   Horizontal scalability ready
-   Backup & disaster recovery compliance

------------------------------------------------------------------------

# 7. FUTURE ROADMAP

Phase 2 Enhancements:

-   Multi-entity consolidation
-   Intercompany accounting
-   Budget vs actual reporting
-   Forecasting engine
-   AI-driven margin anomaly detection
-   Advanced KPI dashboards

------------------------------------------------------------------------

# 8. POSITIONING STATEMENT

LFS is not generic accounting software.

It is a logistics-native financial intelligence system built for 4PL and
3PL enterprises, providing automated financial control, profitability
visibility, and event-driven accounting integrated directly with
operational logistics systems.

------------------------------------------------------------------------

END OF DOCUMENT
