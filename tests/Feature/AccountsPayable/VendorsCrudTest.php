<?php

namespace Tests\Feature\AccountsPayable;

use App\Models\User;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use Database\Seeders\ModulePermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorsCrudTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts-payable.view']);

        return $user;
    }

    private function manager(): User
    {
        $this->seed(ModulePermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts-payable.view', 'accounts-payable.manage']);

        return $user;
    }

    public function test_vendors_index_forbidden_without_view(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('accounts-payable.vendors.index'))
            ->assertForbidden();
    }

    public function test_vendors_show_allowed_with_view(): void
    {
        $vendor = Vendor::create([
            'code' => 'T-V01',
            'name' => 'Test Vendor',
            'currency' => 'USD',
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);
        $this->actingAs($this->viewer())
            ->get(route('accounts-payable.vendors.show', $vendor))
            ->assertOk();
    }

    public function test_create_update_delete_flow(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->manager();
        $this->actingAs($user);

        $this->post(route('accounts-payable.vendors.store'), [
            'code' => 'T-NEW',
            'name' => 'New Co',
            'currency' => 'usd',
            'payment_terms_days' => 15,
            'is_active' => '1',
        ])->assertRedirect();

        $vendor = Vendor::query()->where('code', 'T-NEW')->firstOrFail();
        $this->assertSame('USD', $vendor->currency);
        $this->assertTrue($vendor->is_active);

        $this->put(route('accounts-payable.vendors.update', $vendor), [
            'code' => 'T-NEW',
            'name' => 'Renamed Co',
            'currency' => 'EUR',
            'payment_terms_days' => 20,
            'is_active' => '0',
        ])->assertRedirect(route('accounts-payable.vendors.show', $vendor));

        $vendor->refresh();
        $this->assertSame('Renamed Co', $vendor->name);
        $this->assertFalse($vendor->is_active);

        $this->delete(route('accounts-payable.vendors.destroy', $vendor))
            ->assertRedirect(route('accounts-payable.vendors.index'));

        $this->assertNull(Vendor::query()->where('code', 'T-NEW')->first());
    }

    public function test_cannot_delete_vendor_with_purchase_orders(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\ProcurementDemoSeeder::class);

        $vendor = Vendor::query()->where('code', 'V001')->firstOrFail();
        $this->assertGreaterThan(0, $vendor->purchaseOrders()->count());

        $user = $this->manager();
        $this->actingAs($user)
            ->delete(route('accounts-payable.vendors.destroy', $vendor))
            ->assertRedirect(route('accounts-payable.vendors.show', $vendor))
            ->assertSessionHas('error');

        $this->assertNotNull(Vendor::query()->where('code', 'V001')->first());
    }
}
