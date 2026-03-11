# LFS Financial Event Payload Specification

## API Contract for WMS / LMS Integration

System: LFS -- Logistics Financial System\
Version: 1.0\
Purpose: Define the standard API payload structure used by operational
systems (WMS / LMS) when sending financial events to the LFS Core
Accounting Engine.

------------------------------------------------------------------------

# 1. Overview

The LFS platform uses **event‑driven accounting**.

Operational systems such as:

-   WMS (Warehouse Management System)
-   LMS (Logistics Management System)

send **financial events** to LFS.

These events trigger:

Financial Event\
→ Posting Rule Lookup\
→ Journal Builder\
→ JournalService\
→ General Ledger

To ensure consistency and automation, every financial event must follow
a **standardized payload structure**.

------------------------------------------------------------------------

# 2. API Endpoint Structure

Base Endpoint

POST /api/financial-events/{event_type}

Example

POST /api/financial-events/shipment_delivered

All endpoints require:

-   JWT authentication
-   Idempotency key
-   Signed payload
-   JSON format

------------------------------------------------------------------------

# 3. Standard Event Envelope

Every financial event must include the following **metadata fields**.

  Field                Description
  -------------------- --------------------------------
  event_type           Financial event name
  event_reference_id   Unique operational reference
  source_system        System sending event (WMS/LMS)
  event_timestamp      Time event occurred
  idempotency_key      Unique event identifier
  currency_code        Transaction currency
  tenant_id            Client environment identifier

Example envelope:

``` json
{
  "event_type": "shipment_delivered",
  "event_reference_id": "SHP-10021",
  "source_system": "LMS",
  "event_timestamp": "2026-03-09T14:00:00Z",
  "idempotency_key": "evt_shp_10021",
  "currency_code": "USD",
  "tenant_id": "client_01"
}
```

------------------------------------------------------------------------

# 4. Amount Fields

Financial events must define monetary values in the **amount_fields
object**.

Example:

``` json
"amount_fields": {
  "shipment_revenue": 1200,
  "freight_cost": 800,
  "fuel_cost": 120
}
```

These values are referenced by the **Posting Rules Engine**.

Example:

amount_source = shipment_revenue

------------------------------------------------------------------------

# 5. Dimension Fields

Dimensions allow financial analytics and profitability analysis.

Supported dimensions:

  Field             Description
  ----------------- ----------------------------
  client_id         Customer identifier
  shipment_id       Shipment identifier
  route_id          Transport route
  warehouse_id      Warehouse location
  vehicle_id        Transport vehicle
  project_id        Project logistics job
  service_line_id   Logistics service category
  cost_center_id    Internal cost center

Example:

``` json
"dimension_fields": {
  "client_id": "CL-100",
  "shipment_id": "SHP-10021",
  "route_id": "MNL-CEB",
  "vehicle_id": "TRK-22"
}
```

------------------------------------------------------------------------

# 6. Shipment Revenue Event

Event Type

shipment_delivered

Example Payload

``` json
{
  "event_type": "shipment_delivered",
  "event_reference_id": "SHP-10021",
  "source_system": "LMS",
  "event_timestamp": "2026-03-09T14:00:00Z",
  "idempotency_key": "evt_shp_10021",
  "currency_code": "USD",
  "tenant_id": "client_01",
  "amount_fields": {
    "shipment_revenue": 1200
  },
  "dimension_fields": {
    "client_id": "CL-100",
    "shipment_id": "SHP-10021",
    "route_id": "MNL-CEB"
  }
}
```

Expected Posting

Debit Accounts Receivable\
Credit Transport Revenue

------------------------------------------------------------------------

# 7. Freight Cost Accrual Event

Event Type

freight_cost_accrued

Example Payload

``` json
{
  "event_type": "freight_cost_accrued",
  "event_reference_id": "SHP-10021",
  "source_system": "LMS",
  "event_timestamp": "2026-03-09T14:05:00Z",
  "idempotency_key": "evt_cost_10021",
  "currency_code": "USD",
  "tenant_id": "client_01",
  "amount_fields": {
    "freight_cost": 800
  },
  "dimension_fields": {
    "shipment_id": "SHP-10021",
    "vendor_id": "VENDOR-77"
  }
}
```

Expected Posting

Debit Freight Expense\
Credit Accrued Payables

------------------------------------------------------------------------

# 8. Storage Revenue Accrual Event

Event Type

storage_day_elapsed

Example Payload

``` json
{
  "event_type": "storage_day_elapsed",
  "event_reference_id": "STO-555",
  "source_system": "WMS",
  "event_timestamp": "2026-03-09T23:59:59Z",
  "idempotency_key": "evt_storage_555",
  "currency_code": "USD",
  "tenant_id": "client_01",
  "amount_fields": {
    "storage_fee": 50
  },
  "dimension_fields": {
    "client_id": "CL-100",
    "warehouse_id": "WH-01"
  }
}
```

Expected Posting

Debit Accrued Revenue\
Credit Storage Revenue

------------------------------------------------------------------------

# 9. Client Payment Event

Event Type

client_payment_received

Example Payload

``` json
{
  "event_type": "client_payment_received",
  "event_reference_id": "PAY-300",
  "source_system": "TREASURY",
  "event_timestamp": "2026-03-10T10:00:00Z",
  "idempotency_key": "evt_pay_300",
  "currency_code": "USD",
  "tenant_id": "client_01",
  "amount_fields": {
    "payment_amount": 1200
  },
  "dimension_fields": {
    "client_id": "CL-100"
  }
}
```

Expected Posting

Debit Cash\
Credit Accounts Receivable

------------------------------------------------------------------------

# 10. Idempotency Enforcement

Every event must include:

idempotency_key

The Core Accounting module verifies:

posting_sources.idempotency_key

If duplicate events arrive, the system must:

-   reject the duplicate
-   return success response without posting another journal

------------------------------------------------------------------------

# 11. Error Handling

If payload validation fails:

System response:

HTTP 422

Example

``` json
{
  "error": "INVALID_PAYLOAD",
  "message": "Missing amount_fields.shipment_revenue"
}
```

If posting rule not found:

``` json
{
  "error": "POSTING_RULE_NOT_FOUND"
}
```

------------------------------------------------------------------------

# 12. Security Requirements

Operational systems must:

-   authenticate using JWT token
-   use HTTPS
-   include signed payload headers
-   send requests from whitelisted IPs

------------------------------------------------------------------------

# 13. Performance Considerations

Financial events should be processed asynchronously.

Recommended architecture:

Event API\
→ Queue Worker\
→ Posting Rules Engine\
→ Journal Engine

Benefits:

-   higher throughput
-   system resiliency
-   retry capability

------------------------------------------------------------------------

# 14. Versioning Strategy

API versioning:

/api/v1/financial-events

Future schema changes must maintain backward compatibility.

------------------------------------------------------------------------

# 15. Summary

The Financial Event Payload Specification ensures:

-   standardized financial event messages
-   seamless WMS/LMS integration
-   reliable event‑driven accounting
-   accurate profitability tracking

This specification forms the **official API contract between operational
systems and the LFS Financial Engine**.

------------------------------------------------------------------------

END OF DOCUMENT
