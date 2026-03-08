<?php

namespace App\Modules\BillingEngine\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BillingEngine\Application\RatingService;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\BillingEngine\Infrastructure\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingEngineApiController extends Controller
{
    public function __construct(
        protected RatingService $ratingService,
    ) {
    }

    public function clients(): JsonResponse
    {
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'external_id', 'currency']);
        return response()->json(['clients' => $clients]);
    }

    public function contracts(Request $request): JsonResponse
    {
        $query = Contract::with(['client:id,code,name', 'serviceType:id,code,name'])
            ->where('status', 'active');
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }
        $contracts = $query->orderBy('name')->get();
        return response()->json(['contracts' => $contracts]);
    }

    /**
     * POST or GET - simulate rating. Payload: event_type, client_id or contract_id, event_date, quantity, pallet_days, cbm, kg, trip, container_count
     */
    public function simulate(Request $request): JsonResponse
    {
        $eventType = $request->input('event_type', 'shipment-delivered');
        $payload = $request->only([
            'client_id', 'contract_id', 'external_client_id', 'service_type_code',
            'event_date', 'quantity', 'pallet_days', 'cbm', 'kg', 'trip', 'container_count', 'route_id',
        ]);
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        $result = $this->ratingService->simulate($eventType, $payload);
        return response()->json([
            'contract' => $result['contract'] ? [
                'id' => $result['contract']->id,
                'name' => $result['contract']->name,
                'client_code' => $result['contract']->client->code ?? null,
            ] : null,
            'lines' => $result['lines'],
            'total' => $result['total'],
            'currency' => $result['currency'],
        ]);
    }
}
