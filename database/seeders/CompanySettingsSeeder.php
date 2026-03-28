<?php

namespace Database\Seeders;

use App\Core\Services\SystemSettingsService;
use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Populate the single general_settings row with demo company profile data for UI testing.
     * Safe to run multiple times (updates the first row). Does not set company_logo (use the UI upload).
     */
    public function run(): void
    {
        $row = GeneralSetting::query()->first();

        $attributes = [
            'company_name' => '4PL Demo Logistics Inc.',
            'company_address' => "12 Harbor Exchange Square\nBonifacio Global City\nTaguig, Metro Manila 1634\nPhilippines",
            'company_logo' => null,
            'telephone_number' => '+63 2 8123 4567',
            'email_address' => 'finance@4pl-demo.example',
            'website' => 'https://4pl-demo.example',
            'default_timezone' => 'Asia/Manila',
            'default_date_format' => 'Y-m-d',
            'default_currency' => 'PHP',
            'registration_number' => 'SEC-2024-0001234 / TIN 123-456-789-000',
            'fiscal_year_start_month' => 1,
            'fiscal_year_start_day' => 1,
        ];

        if ($row) {
            $row->update($attributes);
        } else {
            GeneralSetting::query()->create($attributes);
        }

        Cache::forget(SystemSettingsService::CACHE_GENERAL);
    }
}
