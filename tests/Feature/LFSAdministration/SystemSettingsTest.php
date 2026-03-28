<?php

namespace Tests\Feature\LFSAdministration;

use App\Core\Services\AuditService;
use App\Core\Services\SystemSettingsService;
use App\Models\Activity;
use App\Models\FinancialControlSetting;
use App\Models\User;
use App\Modules\CoreAccounting\Application\JournalService;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_settings_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('lfs-administration.settings.company'))->assertStatus(403);
    }

    public function test_company_settings_visible_with_view_permission(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');
        $this->actingAs($user);

        $this->get(route('lfs-administration.settings.company'))
            ->assertOk()
            ->assertDontSee('enctype="multipart/form-data"', false);
    }

    public function test_company_update_logs_configuration_activity(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        Permission::findOrCreate('lfs-administration.manage', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo(['lfs-administration.view', 'lfs-administration.manage']);
        $this->actingAs($user);

        $this->withoutMiddleware(ValidateCsrfToken::class);

        Cache::forget(SystemSettingsService::CACHE_GENERAL);

        $this->put(route('lfs-administration.settings.company.update'), [
            'company_name' => 'Acme Logistics',
            'company_address' => '123 Main',
            'telephone_number' => null,
            'email_address' => 'a@example.com',
            'website' => null,
            'default_timezone' => 'UTC',
            'default_date_format' => 'Y-m-d',
            'default_currency' => 'USD',
            'registration_number' => 'REG-1',
            'fiscal_year_start_month' => 1,
            'fiscal_year_start_day' => 1,
        ])->assertRedirect(route('lfs-administration.settings.company'));

        $this->assertDatabaseHas('general_settings', [
            'company_name' => 'Acme Logistics',
            'default_currency' => 'USD',
        ]);

        $this->assertTrue(
            Activity::query()
                ->where('log_name', AuditService::LOG_CONFIGURATION)
                ->where('event', 'settings.company.updated')
                ->exists()
        );
    }

    public function test_company_update_requires_company_name_by_default(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        Permission::findOrCreate('lfs-administration.manage', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo(['lfs-administration.view', 'lfs-administration.manage']);
        $this->actingAs($user);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::forget(SystemSettingsService::CACHE_GENERAL);

        $this->from(route('lfs-administration.settings.company'))
            ->put(route('lfs-administration.settings.company.update'), [
                'company_name' => '',
                'company_address' => null,
                'telephone_number' => null,
                'email_address' => null,
                'website' => null,
                'default_timezone' => 'UTC',
                'default_date_format' => 'Y-m-d',
                'default_currency' => 'USD',
                'registration_number' => null,
                'fiscal_year_start_month' => null,
                'fiscal_year_start_day' => null,
            ])
            ->assertRedirect(route('lfs-administration.settings.company'))
            ->assertSessionHasErrors('company_name');
    }

    public function test_company_update_rejects_invalid_currency(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        Permission::findOrCreate('lfs-administration.manage', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo(['lfs-administration.view', 'lfs-administration.manage']);
        $this->actingAs($user);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::forget(SystemSettingsService::CACHE_GENERAL);

        $this->from(route('lfs-administration.settings.company'))
            ->put(route('lfs-administration.settings.company.update'), [
                'company_name' => 'Acme',
                'company_address' => null,
                'telephone_number' => null,
                'email_address' => null,
                'website' => null,
                'default_timezone' => 'UTC',
                'default_date_format' => 'Y-m-d',
                'default_currency' => 'XXX',
                'registration_number' => null,
                'fiscal_year_start_month' => null,
                'fiscal_year_start_day' => null,
            ])
            ->assertRedirect(route('lfs-administration.settings.company'))
            ->assertSessionHasErrors('default_currency');
    }

    public function test_company_update_rejects_invalid_fiscal_date(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        Permission::findOrCreate('lfs-administration.manage', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo(['lfs-administration.view', 'lfs-administration.manage']);
        $this->actingAs($user);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::forget(SystemSettingsService::CACHE_GENERAL);

        $this->from(route('lfs-administration.settings.company'))
            ->put(route('lfs-administration.settings.company.update'), [
                'company_name' => 'Acme',
                'company_address' => null,
                'telephone_number' => null,
                'email_address' => null,
                'website' => null,
                'default_timezone' => 'UTC',
                'default_date_format' => 'Y-m-d',
                'default_currency' => 'USD',
                'registration_number' => null,
                'fiscal_year_start_month' => 6,
                'fiscal_year_start_day' => 31,
            ])
            ->assertRedirect(route('lfs-administration.settings.company'))
            ->assertSessionHasErrors('fiscal_year_start_day');
    }

    public function test_company_update_logo_stores_file_and_removes_previous_on_replace(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        Permission::findOrCreate('lfs-administration.manage', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo(['lfs-administration.view', 'lfs-administration.manage']);
        $this->actingAs($user);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::forget(SystemSettingsService::CACHE_GENERAL);

        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('logo1.png', 32, 32);
        $this->put(route('lfs-administration.settings.company.update'), [
            'company_name' => 'Acme Logistics',
            'company_address' => null,
            'telephone_number' => null,
            'email_address' => null,
            'website' => null,
            'default_timezone' => 'UTC',
            'default_date_format' => 'Y-m-d',
            'default_currency' => 'USD',
            'registration_number' => null,
            'fiscal_year_start_month' => null,
            'fiscal_year_start_day' => null,
            'company_logo' => $file1,
        ])->assertRedirect(route('lfs-administration.settings.company'));

        $path1 = \App\Models\GeneralSetting::query()->value('company_logo');
        $this->assertNotNull($path1);
        Storage::disk('public')->assertExists($path1);

        $file2 = UploadedFile::fake()->image('logo2.png', 32, 32);
        $this->put(route('lfs-administration.settings.company.update'), [
            'company_name' => 'Acme Logistics',
            'company_address' => null,
            'telephone_number' => null,
            'email_address' => null,
            'website' => null,
            'default_timezone' => 'UTC',
            'default_date_format' => 'Y-m-d',
            'default_currency' => 'USD',
            'registration_number' => null,
            'fiscal_year_start_month' => null,
            'fiscal_year_start_day' => null,
            'company_logo' => $file2,
        ])->assertRedirect(route('lfs-administration.settings.company'));

        Storage::disk('public')->assertMissing($path1);
        $path2 = \App\Models\GeneralSetting::query()->value('company_logo');
        $this->assertNotSame($path1, $path2);
        Storage::disk('public')->assertExists($path2);
    }

    public function test_journal_post_respects_max_backdating_days(): void
    {
        $fc = FinancialControlSetting::query()->first();
        $this->assertNotNull($fc);
        $fc->update(['max_backdating_days' => 5, 'allow_manual_journals' => true]);
        Cache::forget(SystemSettingsService::CACHE_FINANCIAL);

        $a1 = Account::create([
            'code' => '1000-SS',
            'name' => 'Cash',
            'type' => 'asset',
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);
        $a2 = Account::create([
            'code' => '4000-SS',
            'name' => 'Revenue',
            'type' => 'revenue',
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);

        $journalService = app(JournalService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('backdating');

        $journalService->post([
            ['account_id' => $a1->id, 'debit' => 1, 'credit' => 0],
            ['account_id' => $a2->id, 'debit' => 0, 'credit' => 1],
        ], [
            'journal_date' => now()->subDays(20)->toDateString(),
            'source_system' => 'test',
            'source_reference' => 'ref-ss-1',
            'idempotency_key' => 'idem-ss-1',
        ]);
    }

    public function test_manual_journal_blocked_when_disabled(): void
    {
        $fc = FinancialControlSetting::query()->first();
        $this->assertNotNull($fc);
        $fc->update(['max_backdating_days' => null, 'allow_manual_journals' => false]);
        Cache::forget(SystemSettingsService::CACHE_FINANCIAL);

        $a1 = Account::create([
            'code' => '1000-MJ',
            'name' => 'Cash',
            'type' => 'asset',
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);
        $a2 = Account::create([
            'code' => '4000-MJ',
            'name' => 'Revenue',
            'type' => 'revenue',
            'level' => 1,
            'is_posting' => true,
            'is_active' => true,
        ]);

        $journalService = app(JournalService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manual journals are disabled');

        $journalService->post([
            ['account_id' => $a1->id, 'debit' => 1, 'credit' => 0],
            ['account_id' => $a2->id, 'debit' => 0, 'credit' => 1],
        ], [
            'journal_date' => now()->toDateString(),
        ]);
    }

    public function test_api_settings_company_requires_auth(): void
    {
        $this->getJson('/api/lfs-administration/settings/company')->assertStatus(401);
    }

    public function test_api_settings_company_returns_json_with_sanctum(): void
    {
        $user = User::factory()->create();
        $view = Permission::findOrCreate('lfs-administration.view', 'sanctum');
        $user->givePermissionTo($view);
        Sanctum::actingAs($user);

        $this->getJson('/api/lfs-administration/settings/company')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'company' => [
                    'default_currency',
                    'default_timezone',
                    'company_logo',
                    'logo_url',
                ],
            ]);
    }
}
