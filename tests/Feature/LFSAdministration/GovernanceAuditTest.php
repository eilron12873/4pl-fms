<?php

namespace Tests\Feature\LFSAdministration;

use App\Core\Services\AuditService;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GovernanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('lfs-administration.audit-logs'))->assertStatus(403);
    }

    public function test_audit_logs_ok_with_view_permission(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');
        $this->actingAs($user);

        $this->get(route('lfs-administration.audit-logs'))->assertOk();
    }

    public function test_audit_logs_rejects_inverted_date_range(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');
        $this->actingAs($user);

        $this->get(route('lfs-administration.audit-logs', [
            'from_date' => '2026-03-10',
            'to_date' => '2026-03-01',
        ]))->assertSessionHasErrors('to_date');
    }

    public function test_role_update_creates_security_activity(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        Permission::findOrCreate('lfs-administration.manage', 'web');
        $p1 = Permission::findOrCreate('test.alpha', 'web');
        $p2 = Permission::findOrCreate('test.beta', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo(['lfs-administration.view', 'lfs-administration.manage']);

        $role = Role::create(['name' => 'Governance Test Role', 'guard_name' => 'web']);
        $role->syncPermissions([$p1->name]);

        $this->actingAs($user);

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->put(route('lfs-administration.roles.update', $role->id), [
            'permissions' => [(string) $p2->id],
        ])->assertRedirect(route('lfs-administration.roles'));

        $this->assertDatabaseHas('activities', [
            'log_name' => AuditService::LOG_SECURITY,
            'event' => 'role.permissions_updated',
            'subject_type' => Role::class,
            'subject_id' => (string) $role->id,
        ]);

        $activity = Activity::query()->where('event', 'role.permissions_updated')->first();
        $this->assertNotNull($activity);
        $props = $activity->properties;
        $this->assertContains('test.beta', $props['permissions_added'] ?? []);
        $this->assertContains('test.alpha', $props['permissions_removed'] ?? []);
    }

    public function test_audit_logs_export_requires_date_range_when_configured(): void
    {
        Config::set('audit.export.require_date_range', true);

        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');
        $this->actingAs($user);

        $response = $this->from(route('lfs-administration.audit-logs'))
            ->get(route('lfs-administration.audit-logs.export'));

        $response->assertRedirect(route('lfs-administration.audit-logs'));
        $response->assertSessionHasErrors('from_date');
    }

    public function test_audit_logs_export_streams_csv_within_range(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');
        $this->actingAs($user);

        Activity::create([
            'log_name' => 'default',
            'description' => 'export row',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => ['k' => 'v'],
            'event' => 'test.event',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $response = $this->get(route('lfs-administration.audit-logs.export', [
            'from_date' => $from,
            'to_date' => $to,
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('export row', $response->streamedContent());
    }

    public function test_api_audit_logs_requires_auth(): void
    {
        $this->getJson('/api/lfs-administration/audit-logs')->assertStatus(401);
    }

    public function test_api_audit_logs_returns_items_with_sanctum(): void
    {
        $user = User::factory()->create();
        $view = Permission::findOrCreate('lfs-administration.view', 'sanctum');
        $user->givePermissionTo($view);
        Sanctum::actingAs($user);

        Activity::create([
            'log_name' => 'default',
            'description' => 'api row',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'event' => null,
        ]);

        $response = $this->getJson('/api/lfs-administration/audit-logs?per_page=10');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['items', 'pagination']);
        $items = $response->json('items');
        $this->assertNotEmpty($items);
        $this->assertSame('api row', $items[0]['description']);
    }

    public function test_audit_log_detail_ok_for_viewer(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');
        $this->actingAs($user);

        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'detail test',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => ['a' => 1],
            'event' => 'x',
        ]);

        $this->get(route('lfs-administration.audit-logs.show', $activity))->assertOk()->assertSee('detail test', false);
    }

    public function test_audit_prune_dry_run_when_disabled_reports_disabled(): void
    {
        $this->artisan('audit:prune-activities', ['--dry-run' => true])
            ->expectsOutputToContain('disabled')
            ->assertExitCode(0);
    }
}
