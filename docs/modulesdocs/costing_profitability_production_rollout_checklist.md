# Costing & Profitability Production Rollout Checklist

## Pre-deployment
- Confirm migrations applied for costing settings, allocation rules/results, snapshots, presets, and performance indexes.
- Seed baseline costing configuration (`revenue_prefixes`, `expense_prefixes`, `enabled_dimensions`, `functional_currency`, `fx_rates`).
- Verify permissions include `costing-engine.view` and `costing-engine.manage`.

## Functional smoke tests
- Client profitability loads with date filters and matches AR + GL source totals.
- Shipment/Route/Warehouse/Project profitability screens render and export CSV.
- Drill-down links open profitability details and show journal lines (plus AR invoices for client rows).
- Settings screen updates prefixes/dimensions/functional currency and affects report outputs.
- Allocation engine creates rules and batch run produces allocation result rows.

## Performance and operations
- Run `php artisan costing:snapshots` successfully.
- Scheduler includes daily `costing:snapshots` execution.
- Confirm report caching works and invalidation approach is documented for config/rule changes.
- Validate journal_lines and ar_invoices indexes are present in MySQL.

## Security and governance
- Verify `costing-engine.manage` routes are restricted.
- Ensure module remains read-only for source financial data (no direct journal posting).
- Confirm drill-down preserves filter context for audit traceability.

## Post-deployment validation
- Re-run demo seeders and compare report values with expected test scenarios.
- Validate API endpoints under Sanctum auth for BI consumers.
- Capture baseline page response times before/after snapshot refresh.

