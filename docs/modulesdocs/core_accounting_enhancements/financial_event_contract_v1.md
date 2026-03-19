# Financial Event Contract v1

System: `LFS`  
Module: `Core Accounting`  
Version: `1.0`  
Status: `Frozen`

---

## 1. Naming Standard

Event type naming is **kebab-case** and must be used consistently:

- `shipment-delivered`
- `storage-accrual`
- `vendor-invoice-approved`
- `project-milestone-completed`

Snake case (`shipment_delivered`) is non-canonical and should not be introduced in new integrations.

---

## 2. Required Envelope Fields

Every API request to financial events endpoint must include:

- `idempotency_key` (string, required)
- `source_system` (string, required)
- `source_reference` (string, required)
- `payload` (object, required)

Event type is provided in route path:

- `POST /api/financial-events/{event_type}`

---

## 3. Required Payload Core Fields

Minimum recommended payload fields for posting:

- `journal_date` (ISO date, required for deterministic period assignment)
- `amount` or event-specific amount source fields required by active posting rules
- dimension fields as applicable:
  - `client_id`
  - `shipment_id`
  - `route_id`
  - `warehouse_id`
  - `vehicle_id`
  - `project_id`
  - `service_line`
  - `cost_center`

Posting rules define exact `amount_source` and `dimension_source` resolution for each event.

---

## 4. Idempotency Contract

- `idempotency_key` must be globally unique per business event.
- Repeated submissions with same key must not create duplicate journals.
- Duplicate response should return status `duplicate` with existing `journal_id` when available.

---

## 5. COA Contract Linkage

All account codes referenced by rules or payload overrides must follow:

- 6-digit `XYYZZZ` coding model
- active posting account requirement (`is_posting = true`)
- valid parent hierarchy

Referenced governance documents:

- `coa_governance_standard_v1.md`
- `lfs_coa_structure_and_numbering.md`

---

## 6. Error Contract (Summary)

Error response shape (recommended standard):

```json
{
  "status": "error",
  "error_code": "RULE_NOT_FOUND",
  "message": "No active posting rule found for event type shipment-delivered.",
  "event_type": "shipment-delivered",
  "idempotency_key": "..."
}
```

Canonical `error_code` values:

- `DUPLICATE_EVENT`
- `RULE_NOT_FOUND`
- `RULE_VALIDATION_FAILED`
- `PERIOD_LOCKED`
- `JOURNAL_NOT_BALANCED`
- `INVALID_PAYLOAD`
- `ACCOUNT_RESOLUTION_FAILED`

Detailed behavior is defined in `posting_error_contract_v1.md`.

