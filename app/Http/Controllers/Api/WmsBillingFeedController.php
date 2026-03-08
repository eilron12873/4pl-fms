<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\AccountsReceivable\Application\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives minimal billing data from WMS (or integration layer) to create storage/handling revenue.
 * FMS does not hold custody ledger; WMS sends only what is needed to bill.
 */
class WmsBillingFeedController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {
    }

    /**
     * POST /api/wms-billing/feed
     * Payload: client_id, event_type (storage-accrual | handling-accrual), event_date, pallet_days?, quantity?, reference?
     * Creates a draft AR invoice from contract rates; WMS sends aggregated billable data (e.g. pallet-days for period).
     */
    public function feed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:billing_clients,id'],
            'event_type' => ['required', 'string', 'in:storage-accrual,handling-accrual'],
            'event_date' => ['required', 'date'],
            'pallet_days' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'client_id' => (int) $validated['client_id'],
            'event_date' => $validated['event_date'],
        ];
        if ($validated['event_type'] === 'storage-accrual') {
            $payload['pallet_days'] = (float) ($validated['pallet_days'] ?? 0);
        } else {
            $payload['trip'] = (float) ($validated['quantity'] ?? 0); // handling events as trips for per_trip rate
        }
        if ($validated['event_type'] === 'handling-accrual' && (float) ($validated['quantity'] ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'quantity required for handling-accrual',
            ], 422);
        }
        if ($validated['event_type'] === 'storage-accrual' && (float) ($validated['pallet_days'] ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'pallet_days required for storage-accrual',
            ], 422);
        }

        try {
            $invoice = $this->invoiceService->createInvoiceFromBilling([
                'client_id' => $payload['client_id'],
                'invoice_date' => $validated['event_date'],
                'event_type' => $validated['event_type'],
                'payload' => $payload,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total' => (float) $invoice->total,
            'currency' => $invoice->currency,
        ], 201);
    }
}
