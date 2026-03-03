# LFS -- Logistics Financial System

# AI AGENT IMPLEMENTATION MASTER GUIDE

# Laravel 12 Enterprise Build Instructions

Version 1.0

====================================================================
OBJECTIVE
====================================================================

This document provides detailed, step-by-step instructions for an AI
Agent to fully design and implement the LFS (Logistics Financial System)
using Laravel 12 framework under an enterprise-grade architecture.

The AI Agent must:

• Preserve financial integrity at all times • Enforce immutable ledger
policy • Follow modular architecture strictly • Maintain strict
separation from WMS/LMS • Use Domain-Driven Design (DDD) • Follow
enterprise coding standards

====================================================================
PHASE 0 -- PREPARATION
====================================================================

STEP 0.1 -- Create New Laravel 12 Project

composer create-project laravel/laravel lfs

STEP 0.2 -- Configure Environment

-   Setup .env
-   Configure database (single-tenant)
-   Configure Redis
-   Configure queue driver
-   Set APP_ENV properly

STEP 0.3 -- Install Required Packages

composer require spatie/laravel-permission composer require
laravel/sanctum composer require spatie/laravel-activitylog

====================================================================
PHASE 1 -- ARCHITECTURE STRUCTURE
====================================================================

STEP 1.1 -- Implement Modular Architecture

Create directory:

app/ ├── Modules/ │ ├── CoreAccounting/ │ ├── GeneralLedger/ │ ├──
AccountsReceivable/ │ ├── AccountsPayable/ │ ├── BillingEngine/ │ ├──
CostingEngine/ │ ├── InventoryValuation/ │ ├── FixedAssets/ │ ├──
Treasury/ │ └── FinancialReporting/

Each module must contain:

-   Domain/
-   Application/
-   Infrastructure/
-   Http/
-   Routes/
-   Database/

====================================================================
PHASE 2 -- CORE ACCOUNTING ENGINE
====================================================================

STEP 2.1 -- Create Chart of Accounts Tables

Tables:

accounts account_types cost_centers service_lines

STEP 2.2 -- Build Journal Engine

Tables:

journals journal_lines

Enforce:

-   Debit = Credit validation
-   DB transaction wrapping
-   No update/delete allowed
-   Reversal entry only

STEP 2.3 -- Implement Journal Service

Create:

JournalPostingService JournalValidationService JournalReversalService

All posting must occur via service layer only.

====================================================================
PHASE 3 -- ACCOUNTS RECEIVABLE MODULE
====================================================================

STEP 3.1 -- Create Contract Domain

Tables:

clients contracts contract_rates rate_tiers sla_rules

STEP 3.2 -- Billing Trigger Engine

Create Event Listener:

ShipmentDeliveredListener StorageAccrualListener
ProjectMilestoneListener

Each listener must:

-   Validate contract
-   Generate invoice draft
-   Call JournalPostingService

STEP 3.3 -- Invoice System

Tables:

invoices invoice_lines credit_notes debit_notes payments

====================================================================
PHASE 4 -- ACCOUNTS PAYABLE MODULE
====================================================================

STEP 4.1 -- Vendor Domain

Tables:

vendors vendor_contracts vendor_bills vendor_payments

STEP 4.2 -- Accrual Engine

On shipment completion:

-   Post cost accrual journal

On vendor invoice approval:

-   Reverse accrual
-   Post AP journal

====================================================================
PHASE 5 -- COSTING & PROFITABILITY ENGINE
====================================================================

STEP 5.1 -- Cost Capture Tables

shipment_costs overhead_allocations profitability_snapshots

STEP 5.2 -- Allocation Engine

Implement:

AllocationService

Supports:

-   Revenue proportion
-   Volume proportion
-   Fixed percentage
-   Manual override (approval required)

====================================================================
PHASE 6 -- INVENTORY FINANCIAL CONTROL
====================================================================

STEP 6.1 -- Inventory Valuation Tables

inventory_valuation_layers stock_movements write_offs

Implement:

FIFOService MovingAverageService

All stock movements must generate journal entries.

====================================================================
PHASE 7 -- FIXED ASSET MODULE
====================================================================

Tables:

assets asset_depreciations maintenance_costs

Implement:

DepreciationService

====================================================================
PHASE 8 -- API INTEGRATION LAYER
====================================================================

STEP 8.1 -- Create Financial Events Controller

POST /api/financial-events/{event}

Validate:

-   JWT token
-   Idempotency key
-   Signature

Dispatch internal domain event.

STEP 8.2 -- Implement Idempotency Control

Create table:

event_logs

Prevent duplicate posting.

====================================================================
PHASE 9 -- SECURITY & GOVERNANCE
====================================================================

-   Implement RBAC via Spatie Permission
-   Create financial approval workflow
-   Implement period locking table
-   Prevent journal posting in locked period
-   Enable full audit trail logging

====================================================================
PHASE 10 -- REPORTING ENGINE
====================================================================

Build:

TrialBalanceService IncomeStatementService BalanceSheetService
CashFlowService

Reports must:

-   Be query optimized
-   Use indexed columns
-   Support filtering by branch/service line/client

====================================================================
PHASE 11 -- QUEUE & PERFORMANCE OPTIMIZATION
====================================================================

-   Use queues for financial event processing
-   Separate long-running jobs
-   Add retry policy
-   Add failure logging

====================================================================
PHASE 12 -- TESTING REQUIREMENTS
====================================================================

Implement:

-   Unit tests for journal validation
-   Integration tests for event posting
-   Profitability calculation tests
-   Accrual logic tests

Ensure:

100% coverage for financial posting logic.

====================================================================
PHASE 13 -- DEPLOYMENT
====================================================================

Per Client:

-   Dedicated VPS
-   Install Nginx
-   Install PHP 8.4
-   Install MySQL/PostgreSQL
-   Install Redis
-   Setup Supervisor
-   Configure cron scheduler

====================================================================
CRITICAL ENFORCEMENT RULES
====================================================================

The AI Agent must NEVER:

-   Allow direct DB manipulation of financial tables
-   Allow journal edits
-   Allow deletion of posted entries
-   Allow bypass of approval workflow
-   Allow financial posting outside service layer

====================================================================
FINAL VALIDATION CHECKLIST
====================================================================

Before release:

□ Double-entry enforcement verified □ Period locking functional □ Audit
logs active □ API idempotency working □ Event duplication prevented □
Reports balanced with GL □ Trial Balance zero-difference validated

==================================================================== END
OF AI IMPLEMENTATION MASTER GUIDE
====================================================================
