# Financial Reporting Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Financial Reporting** module provides **advanced management and statutory reports** built on top of Core Accounting, General Ledger, Treasury, AR, and AP data.

Its objectives are to:

- Deliver **management-ready views**: management P&L, P&L by dimension, comparative income statements.
- Support **tax and statutory** requirements via a tax summary.
- Provide integrated **cash flow analysis** that combines GL cash flow with Treasury’s cash position.
- Offer an **AR/AP KPI dashboard** with DSO and margin variance indicators.

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/FinancialReporting`
- **Layers**:
  - `Domain`: `FinancialReporting` domain root.
  - `Application`: `AdvancedReportingService`, `FinancialReportingOverview`.
  - `Infrastructure`: `FinancialReportingRepository`, `FinancialReportingModel`.
  - `UI`: `FinancialReportingController` and Blade views for each report screen.
  - `API`: `api.php` placeholder for reporting APIs.
- **Service provider**: `FinancialReportingServiceProvider` registers module routes, views, and services.

Primary dependencies:

- **GeneralLedger**:
  - `ReportingService` for base financial statements:
    - `incomeStatement()`
    - `incomeStatementByDimension()`
    - `plPerRevenue()`
    - `cashFlowIndirect()`
- **CoreAccounting**:
  - `Period` model for period selection.
- **Treasury**:
  - `TreasuryService::cashPosition()` for cash flow analysis.
- **Accounts Receivable**:
  - `ArReportingService::agingReport()` for AR aging & DSO.
- **Accounts Payable**:
  - `ApReportingService::agingReport()` for AP aging.

---

## 3. Key Components

### 3.1 AdvancedReportingService

`AdvancedReportingService` adds higher-level reporting on top of GL reports:

- **Management summary**
  - `managementSummary(string $fromDate, string $toDate): array`
  - Uses `ReportingService::incomeStatement($fromDate, $toDate)` to build:
    - Sections (revenue, cost of revenue, expenses).
    - Totals: revenue, expense, net income.
    - **Gross margin %** from revenue and cost-of-revenue sections.
  - Computes **YTD net income**:
    - Calls `incomeStatement(yearStart → toDate)` and extracts `net_income`.

- **Comparative income statement**
  - `comparativeIncomeStatement(string $fromDate, string $toDate): array`
  - Uses `incomeStatement()` for:
    - Current period `[fromDate, toDate]`.
    - Prior period of identical length immediately preceding the current period.
  - Outputs:
    - `current` and `prior` income statements.
    - `rows` keyed by section (`key`, `label`) with:
      - Current amount.
      - Prior amount.
      - Variance (current − prior).
      - Variance % vs prior.
    - Aggregates: total revenue/expense and net income for current/prior.

- **Tax summary**
  - `taxSummary(string $fromDate, string $toDate): array`
  - Flattens `incomeStatement()` sections into:
    - Key, label, amount, and `is_revenue` flag (e.g. revenue, other income).
  - Returns:
    - Sections.
    - Totals: revenue, expense, net income.
  - Used for **tax reporting** and high-level revenue/expense breakdown.

### 3.2 FinancialReportingController (UI Layer)

`FinancialReportingController` wires module routes to services and integrates data from GL, Treasury, AR, and AP.

Injected dependencies:

- `AdvancedReportingService` – advanced summaries and comparative reports.
- `ReportingService` – base GL financial statements.
- `TreasuryService` – cash position.
- `ArReportingService`, `ApReportingService` – AR/AP aging for KPIs.

Controller methods:

- `index()` – Financial Reporting home/dashboard.
- `managementReports()` – management P&L summary.
- `taxSummary()` – tax-oriented summary.
- `comparativeIncomeStatement()` – current vs prior income statement.
- `managementPlByDimension()` – management P&L by a dimension (client, warehouse, project).
- `plPerRevenue()` – P&L per revenue block.
- `cashFlowAnalysis()` – GL cash flow + Treasury cash position.
- `kpiDashboard()` – AR/AP KPIs with DSO and margin variance.

All period-based methods:

- Accept `from_date` / `to_date` and optional `period` code.
- When `period` is provided and found, they override dates using `Period::start_date` / `end_date`.

### 3.3 Routes

Defined in `app/Modules/FinancialReporting/routes.php`:

- Prefix: `financial-reporting`
- Name: `financial-reporting.*`
- Middleware: `auth`, `verified`, `permission:financial-reporting.view`

Routes:

- `GET /financial-reporting` → `index()`
- `GET /financial-reporting/management-reports` → `managementReports()`
- `GET /financial-reporting/tax-summary` → `taxSummary()`
- `GET /financial-reporting/comparative-income-statement` → `comparativeIncomeStatement()`
- `GET /financial-reporting/management-pl-dimension` → `managementPlByDimension()`
- `GET /financial-reporting/pl-per-revenue` → `plPerRevenue()`
- `GET /financial-reporting/cash-flow-analysis` → `cashFlowAnalysis()`
- `GET /financial-reporting/kpi-dashboard` → `kpiDashboard()`

---

## 4. Navigation Menus & Screens

### 4.1 Financial Reporting Dashboard

Path: `Financial Reporting → Home` (`/financial-reporting`).

Intro text:

- Explains that these are **advanced** reports combining:
  - Management P&L by dimension.
  - Cash flow (Treasury + GL).
  - AR/AP KPI dashboards.

Cards:

- **Management Reports**
  - Route: `/financial-reporting/management-reports`.
  - Summary P&L for a period plus YTD net income and gross margin %.
- **Comparative Income Statement**
  - Route: `/financial-reporting/comparative-income-statement`.
  - Current vs prior period with variances.
- **Tax Summary**
  - Route: `/financial-reporting/tax-summary`.
  - Revenue and expense by section for tax preparation.
- **Management P&L by Dimension**
  - Route: `/financial-reporting/management-pl-dimension`.
  - P&L broken out by client, warehouse, or project.
- **Cash Flow Analysis**
  - Route: `/financial-reporting/cash-flow-analysis`.
  - Indirect cash flow from GL + real-time cash position from Treasury.
- **AR/AP KPI Dashboard**
  - Route: `/financial-reporting/kpi-dashboard`.
  - Aging, DSO, and margin variance.

Each report screen includes a **Back** link (or navigable breadcrumb) returning to this dashboard.

### 4.2 Management Reports

- Route: `GET /financial-reporting/management-reports`.
- Filters:
  - `Period` (GL period code) – optional dropdown.
  - If no period selected:
    - `from_date` defaults to start of current month.
    - `to_date` defaults to today.
  - If a period is selected:
    - Uses that period’s start and end dates.
- Output:
  - Income statement sections for the period:
    - Revenue, cost of revenue, operating expenses, etc.
  - Totals:
    - Total revenue, total expense, net income.
    - **YTD net income**.
    - **Gross margin %**.
- Use cases:
  - Monthly management review meetings.
  - Quick snapshot of profitability and margin.

### 4.3 Tax Summary

- Route: `GET /financial-reporting/tax-summary`.
- Filters:
  - Same period-selection pattern as management reports.
- Output:
  - Sections:
    - Key, label (e.g. revenue, cost of revenue, operating expenses), amount, and `is_revenue`.
  - Totals:
    - Total revenue, total expense, net income.
- Use cases:
  - Provide simplified view for **tax calculations and filings**.
  - Support external advisor/auditor requests with clear revenue/expense breakdowns.

### 4.4 Comparative Income Statement

- Route: `GET /financial-reporting/comparative-income-statement`.
- Filters:
  - Period or from/to dates, same as other P&L reports.
- Output:
  - For each section (e.g. revenue, cost of revenue, operating expenses):
    - Current period amount.
    - Prior period amount (same number of days immediately preceding).
    - Variance in amount and variance % vs prior.
  - Overall:
    - Total revenue, total expenses, net income for current and prior.
    - Current/prior period ranges (dates) displayed.
- Use cases:
  - Analyze **period-over-period changes** (month-on-month, quarter-on-quarter).
  - Identify material shifts in revenue, costs, or margins.

### 4.5 Management P&L by Dimension

- Route: `GET /financial-reporting/management-pl-dimension`.
- Filters:
  - `from_date`, `to_date` (defaults similar to management reports).
  - `dimension`:
    - `client_id` (default).
    - `warehouse_id`.
    - `project_id`.
- Logic:
  - Uses `ReportingService::incomeStatementByDimension($dimension, $fromDate, $toDate)`.
  - Fetches section labels from `config('gl_statements.income_statement')`.
  - Resolves dimension labels:
    - `client_id` → `BillingClient` (code + name).
    - `warehouse_id` → `Warehouse` (code + name).
    - `project_id` → fallback to raw ID label.
- Output:
  - P&L per dimension with sections (revenue, cost, etc.).
  - Dimension labels for readability (client, warehouse, project).
- Use cases:
  - Management P&L by **client**, **warehouse**, or **project**.
  - Support profitability-based decision making across dimensions.

### 4.6 P&L per Revenue

- Route: `GET /financial-reporting/pl-per-revenue`.
- Filters:
  - Period or from/to dates (same pattern).
- Logic:
  - Uses `ReportingService::plPerRevenue($fromDate, $toDate)` to aggregate P&L by **revenue segments** (e.g. revenue blocks like 41–44).
- Output:
  - P&L sections grouped under revenue categories (e.g. by account prefixes or configured revenue groups).
- Use cases:
  - Understand profitability **per revenue line** or **revenue block** (fulfilling the “P&L per Revenue” requirement from spec).

### 4.7 Cash Flow Analysis

- Route: `GET /financial-reporting/cash-flow-analysis`.
- Filters:
  - `from_date` / `to_date` (defaults to current month).
  - Optional period code to override dates.
- Logic:
  - `reporting->cashFlowIndirect($fromDate, $toDate)`:
    - GL-based indirect cash flow statement (Operating, Investing, Financing).
  - `treasury->cashPosition()`:
    - Current bank account balances by currency.
- Output:
  - GL cash flow for selected period.
  - Treasury cash position side by side:
    - Per-account and per-currency totals.
- Use cases:
  - Cash flow analysis that ties **GL movements** to real-world **bank balances**.
  - Support treasury and finance in liquidity planning.

### 4.8 AR/AP KPI Dashboard

- Route: `GET /financial-reporting/kpi-dashboard`.
- Filters:
  - `as_of_date` (for AR/AP aging; defaults to today).
  - `from_date`, `to_date` (for revenue window; defaults to current month).
- Logic:
  - AR & AP:
    - `arReporting->agingReport($asOfDate)`.
    - `apReporting->agingReport($asOfDate)`.
    - Compute totals for AR and AP.
  - DSO:
    - Uses `reporting->incomeStatement($fromDate, $toDate)['total_revenue']`.
    - Converts revenue into daily rate and compares AR total to derive **Days Sales Outstanding (DSO)**.
  - Margin variance:
    - Calls `advancedReporting->comparativeIncomeStatement($fromDate, $toDate)`:
      - Extracts net income and total revenue for current/prior period.
    - Computes:
      - Current and prior net margin %.
      - Margin variance % (difference between current and prior).
- Output:
  - AR and AP totals (and optionally breakdowns).
  - DSO figure for the period.
  - Margin % current, margin % prior, and variance.
- Use cases:
  - High-level **health dashboard** for CFO/finance leadership:
    - Liquidity risk (AR vs AP).
    - Collections performance (DSO).
    - Profitability trends (margin variance).

---

## 5. End-to-End Workflows

### 5.1 Month-End Management Reporting

1. GL and subledgers (AR/AP, Fixed Assets, etc.) are closed for the month.
2. Finance navigates to **Management Reports**:
   - Selects the closed period or enters from/to dates.
   - Reviews income statement sections, YTD net income, and gross margin %.
3. Uses **Comparative Income Statement**:
   - Compares month vs prior month or same-length prior period.
   - Identifies major changes needing commentary.
4. Uses **P&L per Revenue** and **Management P&L by Dimension**:
   - Breaks down profitability by revenue lines, clients, warehouses, or projects.

### 5.2 Cash Flow & Liquidity Review

1. Team opens **Cash Flow Analysis**:
   - Selects desired period.
   - Reviews GL-based indirect cash flow (Operating/Investing/Financing).
   - Compares to Treasury cash position for real-world balances.
2. Uses insights to:
   - Align AP payment schedules and AR collection efforts.
   - Plan financing, investments, or cash pooling.

### 5.3 KPI & Collections Monitoring

1. Collections/finance uses **AR/AP KPI Dashboard** weekly or daily:
   - Checks AR and AP totals and their aging.
   - Monitors DSO over time.
   - Assesses changes in profit margin vs prior period.
2. Takes actions:
   - Focus collections on large/overdue accounts.
   - Adjust credit terms or pricing as needed.

---

## 6. Design Decisions & Guarantees

- **Reuse of Core GL Logic**
  - Most reporting logic relies on `ReportingService` from GeneralLedger, ensuring:
    - A single source of truth for financial statements.
    - Consistent section definitions via `config('gl_statements')`.

- **Period-Aware Reporting**
  - All major reports allow selection by **period code** or by raw dates.
  - This aligns financial reporting with formal GL period structure.

- **Cross-Module Integration**
  - KPIs and cash flow analysis integrate data from:
    - GL, Treasury, AR, AP, and Costing where relevant.
  - This provides a **consolidated financial view** without duplicating data.

---

## 7. Recommended Enhancements

These are **optional improvements** to further strengthen the Financial Reporting module.

### 7.1 Saved Report Configurations & Favorites

- Allow users to:
  - Save **report presets** (e.g. specific date ranges, dimensions, filters).
  - Mark key reports as favorites for quick access.

### 7.2 Export & API Access

- Provide:
  - CSV/Excel export for each report.
  - Read-only API endpoints (e.g. JSON) for embedding in BI tools or external dashboards.

### 7.3 Drill-Down & Traceability

- Enhance reports with:
  - Drill-down from P&L sections to **GL journals and lines**.
  - Links from AR/AP KPIs back to underlying invoices/bills.
  - This deepens auditability and speeds up analysis.

### 7.4 Budget vs Actual & Variance Analysis

- Introduce:
  - Budget data structures and entry screens.
  - Reports comparing **Actual vs Budget** with variance analysis alongside current vs prior.

### 7.5 Additional KPIs & Trend Visualizations

- Add:
  - Graphs for revenue, margin, DSO, and AR/AP totals over time.
  - Additional KPIs such as:
    - Cash conversion cycle.
    - Operating margin vs net margin.

### 7.6 Multi-Entity & Consolidation

- For multi-entity setups:
  - Add support for entity filters and consolidation rules.
  - Provide consolidated and entity-level versions of each report.

---

## 8. Summary

The Financial Reporting module provides a rich suite of **management and statutory reports** by:

- Building on top of GL, Treasury, AR, and AP.
- Delivering flexible, period-aware income statements and cash flow.
- Offering KPI dashboards that unify liquidity, collections, and profitability signals.

The recommended enhancements focus on drill-down, automation, budgeting, and visualization to further empower finance and management users.

