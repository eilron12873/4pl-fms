# WMS Billing Feed API

The FMS does not hold a custody ledger. The WMS (or integration layer) sends **minimal billable data** so the FMS can create storage and handling revenue.

## Endpoint

- **URL:** `POST /api/wms-billing/feed`
- **Auth:** Bearer token (Sanctum); user must have permission `integration.wms-billing`.
- **Content-Type:** `application/json`

## Payload

| Field        | Type   | Required | Description |
|-------------|--------|----------|-------------|
| `client_id` | int    | Yes      | Billing client ID (from FMS `billing_clients`). |
| `event_type`| string | Yes      | `storage-accrual` or `handling-accrual`. |
| `event_date`| string | Yes      | Date (Y-m-d) for the billing period or event. |
| `pallet_days` | number | For storage | Total pallet-days (e.g. for the period) for storage-accrual. |
| `quantity`  | number | For handling | Number of handling events/movements for handling-accrual. |
| `reference` | string | No       | Optional reference (e.g. WMS batch id). |

## Event types

### storage-accrual

- **Requires:** `pallet_days` > 0.
- **Use:** WMS sends aggregated pallet-days (e.g. daily or monthly) for a client. FMS finds the client’s **storage** contract and per-pallet-day rate, creates a draft AR invoice with one or more lines.

### handling-accrual

- **Requires:** `quantity` > 0.
- **Use:** WMS sends number of handling events (e.g. receipts, issues, moves). FMS finds the client’s **handling** contract and per-trip/movement rate, creates a draft AR invoice.

## Response

**201 Created** (success):

```json
{
  "success": true,
  "invoice_id": 123,
  "invoice_number": "INV-2026-001",
  "total": 450.00,
  "currency": "USD"
}
```

**422 Unprocessable Entity** (validation or no rate):

- Missing or invalid fields.
- `pallet_days` required for storage-accrual; `quantity` required for handling-accrual.
- No contract/rate found for the client and service type (storage or handling).

## Setup in FMS

1. **Clients:** Ensure the client exists in **Billing Engine** (billing_clients).
2. **Contracts:** Create a contract per client for **storage** (service type code `storage`) and/or **handling** (service type code `handling`). Add rate definitions (e.g. `per_pallet_day` for storage, `per_trip` for handling).
3. **Service types:** If not present, seed or create service types with codes `storage` and `handling` (see BillingEngine seeders / ServiceType model).
4. **Permission:** Assign `integration.wms-billing` to the user or token used by the WMS/integration layer.

## Example (storage)

```bash
curl -X POST https://your-fms.example.com/api/wms-billing/feed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "event_type": "storage-accrual",
    "event_date": "2026-03-10",
    "pallet_days": 120.5
  }'
```

## Example (handling)

```bash
curl -X POST https://your-fms.example.com/api/wms-billing/feed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "event_type": "handling-accrual",
    "event_date": "2026-03-10",
    "quantity": 45
  }'
```

Created invoices are in **draft** status; issue them from the AR Invoices screen (or via your process) to post revenue.
