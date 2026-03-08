<?php

namespace App\Modules\BillingEngine\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BillingEngine\Application\RatingService;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\BillingEngine\Infrastructure\Models\Contract;
use App\Modules\BillingEngine\Infrastructure\Models\ContractRateDefinition;
use App\Modules\BillingEngine\Infrastructure\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingEngineController extends Controller
{
    public function __construct(
        protected RatingService $ratingService,
    ) {
    }

    public function index(): View
    {
        return view('billing-engine::index');
    }

    public function clients(): View
    {
        $clients = BillingClient::orderBy('code')->paginate(20);
        return view('billing-engine::clients.index', compact('clients'));
    }

    public function clientCreate(): View
    {
        return view('billing-engine::clients.create');
    }

    public function clientStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:billing_clients,code'],
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        BillingClient::create($data);
        return redirect()->route('billing-engine.clients.index')->with('success', __('Client created.'));
    }

    public function clientEdit(int $client): View
    {
        $client = BillingClient::findOrFail($client);
        return view('billing-engine::clients.edit', compact('client'));
    }

    public function clientUpdate(Request $request, int $client): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('billing_clients', 'code')->ignore($client->id)],
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $model = BillingClient::findOrFail($client);
        $model->update($data);
        return redirect()->route('billing-engine.clients.index')->with('success', __('Client updated.'));
    }

    public function contracts(Request $request): View
    {
        $query = Contract::with(['client', 'serviceType']);
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        $contracts = $query->orderByDesc('effective_from')->paginate(15);
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();
        return view('billing-engine::contracts.index', compact('contracts', 'clients'));
    }

    public function contractCreate(): View
    {
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();
        $serviceTypes = ServiceType::orderBy('code')->get();
        return view('billing-engine::contracts.create', compact('clients', 'serviceTypes'));
    }

    public function contractStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:billing_clients,id'],
            'service_type_id' => ['required', 'exists:service_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:50', 'unique:contracts,contract_number'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:draft,active,expired'],
            'sla_terms' => ['nullable', 'string'],
        ]);
        $contract = Contract::create($data);
        return redirect()->route('billing-engine.contracts.show', $contract)->with('success', __('Contract created.'));
    }

    public function contractShow(int $contract): View
    {
        $contract = Contract::with(['client', 'serviceType', 'rateDefinitions', 'slaPenaltyRules'])->findOrFail($contract);
        return view('billing-engine::contracts.show', compact('contract'));
    }

    public function contractEdit(int $contract): View
    {
        $contract = Contract::with('rateDefinitions')->findOrFail($contract);
        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();
        $serviceTypes = ServiceType::orderBy('code')->get();
        return view('billing-engine::contracts.edit', compact('contract', 'clients', 'serviceTypes'));
    }

    public function contractUpdate(Request $request, int $contract): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:billing_clients,id'],
            'service_type_id' => ['required', 'exists:service_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:50', Rule::unique('contracts', 'contract_number')->ignore($contract->id)],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:draft,active,expired'],
            'sla_terms' => ['nullable', 'string'],
        ]);
        $model = Contract::findOrFail($contract);
        $model->update($data);
        return redirect()->route('billing-engine.contracts.show', $model)->with('success', __('Contract updated.'));
    }

    public function rateSimulation(Request $request): View
    {
        $result = null;
        $eventType = $request->string('event_type')->toString() ?: 'shipment-delivered';
        $payload = [
            'client_id' => $request->integer('client_id') ?: null,
            'contract_id' => $request->integer('contract_id') ?: null,
            'event_date' => $request->string('event_date')->toString() ?: now()->toDateString(),
            'quantity' => $request->input('quantity', 1),
            'pallet_days' => $request->input('pallet_days'),
            'cbm' => $request->input('cbm'),
            'kg' => $request->input('kg'),
            'trip' => $request->input('trip', 1),
            'container_count' => $request->input('container_count'),
        ];
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        if ($request->has('event_type') || $request->filled('client_id') || $request->filled('contract_id')) {
            $result = $this->ratingService->simulate($eventType, $payload);
        }

        $clients = BillingClient::where('is_active', true)->orderBy('code')->get();
        $contracts = Contract::with('client')->where('status', 'active')->orderBy('name')->get();
        $serviceTypes = ServiceType::orderBy('code')->get();

        return view('billing-engine::rate-simulation', [
            'result' => $result,
            'eventType' => $eventType,
            'payload' => $payload,
            'clients' => $clients,
            'contracts' => $contracts,
            'serviceTypes' => $serviceTypes,
        ]);
    }

    public function storeRateDefinition(Request $request, int $contract): RedirectResponse
    {
        $contract = Contract::findOrFail($contract);
        $data = $request->validate([
            'rate_type' => ['required', 'string', 'in:per_pallet_day,per_cbm,per_kg,per_trip,per_route,per_container,fixed'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_quantity' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $data['contract_id'] = $contract->id;
        $data['sort_order'] = $contract->rateDefinitions()->max('sort_order') + 1;
        ContractRateDefinition::create($data);
        return redirect()->route('billing-engine.contracts.show', $contract)->with('success', __('Rate added.'));
    }

    public function destroyRateDefinition(int $contract, int $rate): RedirectResponse
    {
        $contract = Contract::findOrFail($contract);
        ContractRateDefinition::where('contract_id', $contract->id)->where('id', $rate)->firstOrFail()->delete();
        return redirect()->route('billing-engine.contracts.show', $contract)->with('success', __('Rate removed.'));
    }
}
