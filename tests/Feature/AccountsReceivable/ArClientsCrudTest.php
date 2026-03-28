<?php

namespace Tests\Feature\AccountsReceivable;

use App\Models\User;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use App\Modules\BillingEngine\Infrastructure\Models\Contract;
use App\Modules\BillingEngine\Infrastructure\Models\ServiceType;
use Database\Seeders\ModulePermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArClientsCrudTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts-receivable.view']);

        return $user;
    }

    private function manager(): User
    {
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts-receivable.view', 'accounts-receivable.manage']);

        return $user;
    }

    /** Client master-data permission without full AR manage (e.g. Staff role). */
    private function clientOnlyManager(): User
    {
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts-receivable.view', 'accounts-receivable.clients.manage']);

        return $user;
    }

    public function test_clients_index_forbidden_without_view(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('accounts-receivable.clients.index'))
            ->assertForbidden();
    }

    public function test_clients_show_allowed_with_view(): void
    {
        $client = BillingClient::create([
            'code' => 'T-CL01',
            'name' => 'Test Client',
            'currency' => 'USD',
            'is_active' => true,
        ]);
        $this->actingAs($this->viewer())
            ->get(route('accounts-receivable.clients.show', $client))
            ->assertOk();
    }

    public function test_create_forbidden_without_manage_or_clients_manage(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('accounts-receivable.clients.create'))
            ->assertForbidden();
    }

    public function test_clients_manage_allows_create_and_toggle_without_full_ar_manage(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->clientOnlyManager();
        $this->actingAs($user);

        $this->get(route('accounts-receivable.clients.create'))->assertOk();

        $this->post(route('accounts-receivable.clients.store'), [
            'code' => 'T-CLM',
            'name' => 'Clients manage only',
            'currency' => 'USD',
            'is_active' => '1',
        ])->assertRedirect();

        $client = BillingClient::query()->where('code', 'T-CLM')->firstOrFail();
        $this->assertTrue($client->is_active);

        $this->post(route('accounts-receivable.clients.toggle-active', $client))
            ->assertRedirect();
        $client->refresh();
        $this->assertFalse($client->is_active);
    }

    public function test_create_update_delete_flow(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->manager();
        $this->actingAs($user);

        $this->post(route('accounts-receivable.clients.store'), [
            'code' => 'T-NEW',
            'name' => 'New Client LLC',
            'external_id' => 'EXT-1',
            'currency' => 'usd',
            'is_active' => '1',
        ])->assertRedirect();

        $client = BillingClient::query()->where('code', 'T-NEW')->firstOrFail();
        $this->assertSame('USD', $client->currency);
        $this->assertTrue($client->is_active);

        $this->put(route('accounts-receivable.clients.update', $client), [
            'code' => 'T-NEW',
            'name' => 'Renamed Client',
            'external_id' => 'EXT-1',
            'currency' => 'EUR',
            'is_active' => '0',
        ])->assertRedirect(route('accounts-receivable.clients.show', $client));

        $client->refresh();
        $this->assertSame('Renamed Client', $client->name);
        $this->assertFalse($client->is_active);

        $this->delete(route('accounts-receivable.clients.destroy', $client))
            ->assertRedirect(route('accounts-receivable.clients.index'));

        $this->assertNull(BillingClient::query()->where('code', 'T-NEW')->first());
    }

    public function test_cannot_delete_client_with_invoice(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->manager();
        $this->actingAs($user);

        $client = BillingClient::create([
            'code' => 'T-INV',
            'name' => 'Has Invoice',
            'currency' => 'USD',
            'is_active' => true,
        ]);
        ArInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-T-00001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
            'amount_allocated' => 0,
            'currency' => 'USD',
        ]);

        $this->delete(route('accounts-receivable.clients.destroy', $client))
            ->assertRedirect(route('accounts-receivable.clients.show', $client))
            ->assertSessionHas('error');

        $this->assertNotNull(BillingClient::query()->where('code', 'T-INV')->first());
    }

    public function test_cannot_delete_client_with_contract(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->manager();
        $this->actingAs($user);

        $serviceType = ServiceType::create([
            'code' => 't_svc_'.uniqid(),
            'name' => 'Test Service',
        ]);
        $client = BillingClient::create([
            'code' => 'T-CTR',
            'name' => 'Has Contract',
            'currency' => 'USD',
            'is_active' => true,
        ]);
        Contract::create([
            'client_id' => $client->id,
            'service_type_id' => $serviceType->id,
            'name' => 'Test contract',
            'contract_number' => 'CN-T-'.uniqid(),
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
            'status' => 'draft',
        ]);

        $this->delete(route('accounts-receivable.clients.destroy', $client))
            ->assertRedirect(route('accounts-receivable.clients.show', $client))
            ->assertSessionHas('error');

        $this->assertNotNull(BillingClient::query()->where('code', 'T-CTR')->first());
    }
}
