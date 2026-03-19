# Posting Error Contract v1

System: `LFS`  
Module: `Core Accounting`  
Version: `1.0`  
Status: `Frozen`

---

## 1. Purpose

Define deterministic response codes and payloads for financial event posting outcomes.

---

## 2. Success and Duplicate Responses

### Posted

- HTTP: `201`
- Body:

```json
{
  "status": "posted",
  "journal_id": 12345
}
```

### Duplicate

- HTTP: `200`
- Body:

```json
{
  "status": "duplicate",
  "journal_id": 12345
}
```

---

## 3. Error Response Structure

For deterministic failures:

- HTTP: `4xx` (client/config issues) or `5xx` (unexpected server errors)
- Body:

```json
{
  "status": "error",
  "error_code": "RULE_NOT_FOUND",
  "message": "No active posting rule found for event type shipment-delivered.",
  "event_type": "shipment-delivered",
  "idempotency_key": "abc-123"
}
```

Required keys:

- `status`
- `error_code`
- `message`
- `event_type` (if available)
- `idempotency_key` (if available)

---

## 4. Canonical Error Codes

- `INVALID_PAYLOAD`
  - missing required envelope fields, malformed payload
- `RULE_NOT_FOUND`
  - no active rule for event and rules-only mode requires rule
- `RULE_VALIDATION_FAILED`
  - rule exists but is invalid/incomplete
- `ACCOUNT_RESOLUTION_FAILED`
  - account ID/code cannot be resolved
- `PERIOD_LOCKED`
  - posting date falls in closed or undefined period
- `JOURNAL_NOT_BALANCED`
  - debit and credit totals mismatch
- `DUPLICATE_EVENT`
  - duplicate idempotency event (if treated as error in some workflows)
- `INTERNAL_ERROR`
  - unexpected unclassified failure

---

## 5. Recommended HTTP Mapping

- `INVALID_PAYLOAD` -> `422`
- `RULE_NOT_FOUND` -> `422`
- `RULE_VALIDATION_FAILED` -> `422`
- `ACCOUNT_RESOLUTION_FAILED` -> `422`
- `PERIOD_LOCKED` -> `409`
- `JOURNAL_NOT_BALANCED` -> `422`
- `DUPLICATE_EVENT` -> `200` with `status=duplicate` (preferred)
- `INTERNAL_ERROR` -> `500`

---

## 6. Logging Requirement

All error responses must also create integration log records with:

- `event_type`
- `idempotency_key`
- `source_system`
- `source_reference`
- `status=error`
- `message`

