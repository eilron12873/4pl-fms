<?php

namespace App\Modules\AccountsReceivable\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArClientsController extends Controller
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function index(): View
    {
        $clients = BillingClient::query()->orderBy('code')->paginate(20);

        return view('accounts-receivable::clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('accounts-receivable::clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = BillingClient::prepareValidatedPayload($request);
        $data['is_active'] = $request->boolean('is_active');
        $client = BillingClient::create($data);
        $this->audit->log(
            description: __('AR client :code created', ['code' => $client->code]),
            event: 'ar.client.created',
            subject: $client,
            properties: ['client_code' => $client->code],
        );

        return redirect()->route('accounts-receivable.clients.show', $client)->with('success', __('Client created.'));
    }

    public function show(BillingClient $client): View
    {
        $client->loadCount(['arInvoices', 'arPayments', 'contracts']);

        return view('accounts-receivable::clients.show', compact('client'));
    }

    public function edit(BillingClient $client): View
    {
        return view('accounts-receivable::clients.edit', compact('client'));
    }

    public function update(Request $request, BillingClient $client): RedirectResponse
    {
        $before = [
            'code' => $client->code,
            'name' => $client->name,
            'is_active' => $client->is_active,
            'currency' => $client->currency,
        ];
        $data = BillingClient::prepareValidatedPayload($request, $client->id);
        $data['is_active'] = $request->boolean('is_active');
        $client->update($data);
        $this->audit->log(
            description: __('AR client :code updated', ['code' => $client->code]),
            event: 'ar.client.updated',
            subject: $client,
            properties: [
                'before' => $before,
                'after' => [
                    'code' => $client->code,
                    'name' => $client->name,
                    'is_active' => $client->is_active,
                    'currency' => $client->currency,
                ],
            ],
        );

        return redirect()->route('accounts-receivable.clients.show', $client)->with('success', __('Client updated.'));
    }

    public function destroy(BillingClient $client): RedirectResponse
    {
        $client->loadCount(['arInvoices', 'arPayments', 'contracts']);
        if ($client->ar_invoices_count > 0 || $client->ar_payments_count > 0 || $client->contracts_count > 0) {
            return redirect()
                ->route('accounts-receivable.clients.show', $client)
                ->with('error', __('This client cannot be deleted because it has invoices, payments, or billing contracts. Deactivate the client instead.'));
        }
        $code = $client->code;
        $id = $client->id;
        $client->delete();
        $this->audit->log(
            description: __('AR client :code deleted', ['code' => $code]),
            event: 'ar.client.deleted',
            subject: null,
            properties: ['client_id' => $id, 'client_code' => $code],
        );

        return redirect()->route('accounts-receivable.clients.index')->with('success', __('Client deleted.'));
    }

    public function toggleActive(BillingClient $client): RedirectResponse
    {
        $before = (bool) $client->is_active;
        $client->update(['is_active' => ! $client->is_active]);
        $client->refresh();
        $this->audit->log(
            description: $client->is_active
                ? __('AR client :code activated', ['code' => $client->code])
                : __('AR client :code deactivated', ['code' => $client->code]),
            event: 'ar.client.updated',
            subject: $client,
            properties: [
                'before' => ['is_active' => $before],
                'after' => ['is_active' => $client->is_active],
            ],
        );

        return redirect()->back()->with('success', $client->is_active ? __('Client activated.') : __('Client deactivated.'));
    }
}
