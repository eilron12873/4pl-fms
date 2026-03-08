<?php

namespace App\Modules\BillingEngine\Application;

use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\BillingEngine\Infrastructure\Models\Contract;
use App\Modules\BillingEngine\Infrastructure\Models\ContractRateDefinition;
use App\Modules\BillingEngine\Infrastructure\Models\ServiceType;
use Carbon\Carbon;

class RatingService
{
    /**
     * Rate an event: find contract, resolve rate definitions, compute line amounts.
     *
     * @param  array<string, mixed>  $payload  e.g. client_id, external_client_id, quantity, cbm, kg, trip, route_id, container_count, event_date, service_type_code
     * @return array<int, array{description: string, rate_type: string, quantity: float, unit_price: float, amount: float, currency: string}>
     */
    public function rate(string $eventType, array $payload): array
    {
        $eventDate = isset($payload['event_date']) ? Carbon::parse($payload['event_date'])->toDateString() : now()->toDateString();
        $contract = $this->resolveContract($payload, $eventType, $eventDate);
        if (! $contract) {
            return [];
        }

        $rates = $contract->rateDefinitions()
            ->where(function ($q) use ($eventDate) {
                $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $eventDate);
            })
            ->where(function ($q) use ($eventDate) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $eventDate);
            })
            ->orderBy('sort_order')
            ->orderBy('min_quantity')
            ->get();

        $lines = [];
        $serviceType = $contract->serviceType;
        $defaultRateType = $serviceType ? ServiceType::rateTypeForServiceCode($serviceType->code) : 'per_trip';

        foreach ($this->extractQuantitiesByRateType($payload, $eventType, $defaultRateType) as $rateType => $quantity) {
            if ($quantity <= 0) {
                continue;
            }
            $rateDef = $this->findApplicableRate($rates, $rateType, $quantity);
            if (! $rateDef) {
                continue;
            }
            $amount = round((float) $rateDef->unit_price * $quantity, 2);
            $lines[] = [
                'description' => $rateDef->description ?: $this->defaultDescription($rateType, $quantity),
                'rate_type' => $rateType,
                'quantity' => $quantity,
                'unit_price' => (float) $rateDef->unit_price,
                'amount' => $amount,
                'currency' => $rateDef->currency,
            ];
        }

        return $lines;
    }

    /**
     * Simulate rating for display (same as rate but returns contract info and breakdown).
     *
     * @param  array<string, mixed>  $payload
     * @return array{contract: Contract|null, lines: array, total: float, currency: string}
     */
    public function simulate(string $eventType, array $payload): array
    {
        $eventDate = isset($payload['event_date']) ? Carbon::parse($payload['event_date'])->toDateString() : now()->toDateString();
        $contract = $this->resolveContract($payload, $eventType, $eventDate);
        if (! $contract) {
            return [
                'contract' => null,
                'lines' => [],
                'total' => 0,
                'currency' => $payload['currency'] ?? 'USD',
            ];
        }

        $lines = $this->rate($eventType, $payload);
        $currency = $contract->client->currency ?? 'USD';
        $total = array_sum(array_column($lines, 'amount'));

        return [
            'contract' => $contract,
            'lines' => $lines,
            'total' => round($total, 2),
            'currency' => $currency,
        ];
    }

    protected function resolveContract(array $payload, string $eventType, string $eventDate): ?Contract
    {
        if (! empty($payload['contract_id'])) {
            $contract = Contract::with(['client', 'serviceType', 'rateDefinitions'])->find($payload['contract_id']);
            return $contract && $contract->isActiveOn($eventDate) ? $contract : null;
        }

        $clientId = $payload['client_id'] ?? null;
        if (! $clientId && ! empty($payload['external_client_id'])) {
            $client = BillingClient::where('external_id', $payload['external_client_id'])->first();
            $clientId = $client?->id;
        }
        if (! $clientId) {
            return null;
        }

        $serviceTypeCode = $payload['service_type_code'] ?? $this->eventTypeToServiceCode($eventType);
        $serviceType = ServiceType::where('code', $serviceTypeCode)->first();
        if (! $serviceType) {
            return null;
        }

        $contract = Contract::with(['client', 'serviceType', 'rateDefinitions'])
            ->where('client_id', $clientId)
            ->where('service_type_id', $serviceType->id)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $eventDate)
            ->where(function ($q) use ($eventDate) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $eventDate);
            })
            ->orderByDesc('effective_from')
            ->first();

        return $contract;
    }

    protected function eventTypeToServiceCode(string $eventType): string
    {
        $map = [
            'shipment-delivered' => 'freight',
            'storage-accrual' => 'storage',
            'handling-accrual' => 'handling',
            'project-milestone-completed' => 'project_milestone',
        ];
        return $map[$eventType] ?? 'freight';
    }

    /**
     * @return array<string, float>
     */
    protected function extractQuantitiesByRateType(array $payload, string $eventType, string $defaultRateType): array
    {
        $out = [];
        $palletDays = (float) ($payload['pallet_days'] ?? $payload['quantity'] ?? 0);
        $cbm = (float) ($payload['cbm'] ?? 0);
        $kg = (float) ($payload['kg'] ?? 0);
        $trip = (float) (isset($payload['trip']) || isset($payload['trips']) ? ($payload['trip'] ?? $payload['trips'] ?? 1) : 0);
        $route = (float) (isset($payload['route_id']) ? 1 : 0);
        $container = (float) ($payload['container_count'] ?? $payload['containers'] ?? 0);
        $fixed = 1.0;

        if ($palletDays > 0) {
            $out['per_pallet_day'] = $palletDays;
        }
        if ($cbm > 0) {
            $out['per_cbm'] = $cbm;
        }
        if ($kg > 0) {
            $out['per_kg'] = $kg;
        }
        if ($trip > 0) {
            $out['per_trip'] = $trip;
        }
        if ($route > 0) {
            $out['per_route'] = $route;
        }
        if ($container > 0) {
            $out['per_container'] = $container;
        }

        if (empty($out)) {
            $out[$defaultRateType] = $payload['quantity'] ?? 1;
        }

        return $out;
    }

    protected function findApplicableRate(\Illuminate\Support\Collection $rates, string $rateType, float $quantity): ?ContractRateDefinition
    {
        $candidates = $rates->where('rate_type', $rateType)->values();
        foreach ($candidates as $rate) {
            if ($rate->appliesToQuantity($quantity)) {
                return $rate;
            }
        }
        return $candidates->first();
    }

    protected function defaultDescription(string $rateType, float $quantity): string
    {
        $labels = [
            'per_pallet_day' => 'Storage',
            'per_cbm' => 'CBM',
            'per_kg' => 'Weight',
            'per_trip' => 'Trip',
            'per_route' => 'Route',
            'per_container' => 'Container',
            'fixed' => 'Fee',
        ];
        $label = $labels[$rateType] ?? $rateType;
        return $label . ' × ' . $quantity;
    }
}
