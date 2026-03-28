<?php

namespace App\Core\Services;

use App\Models\FinancialControlSetting;
use App\Models\GeneralSetting;
use App\Models\TaxCode;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    public const CACHE_GENERAL = 'system_settings.general';

    public const CACHE_FINANCIAL = 'system_settings.financial';

    public const CACHE_TAX_CODES = 'system_settings.tax_codes';

    public function general(): GeneralSetting
    {
        return Cache::rememberForever(self::CACHE_GENERAL, function () {
            $row = GeneralSetting::query()->first();
            if ($row) {
                return $row;
            }

            return GeneralSetting::create([
                'default_timezone' => 'Asia/Manila',
                'default_date_format' => 'Y-m-d',
                'default_currency' => 'PHP',
            ]);
        });
    }

    public function forgetGeneral(): void
    {
        Cache::forget(self::CACHE_GENERAL);
    }

    public function financialControls(): FinancialControlSetting
    {
        return Cache::rememberForever(self::CACHE_FINANCIAL, function () {
            $row = FinancialControlSetting::query()->first();
            if ($row) {
                return $row;
            }

            return FinancialControlSetting::create([
                'max_backdating_days' => null,
                'allow_manual_journals' => true,
                'thresholds' => null,
            ]);
        });
    }

    public function forgetFinancial(): void
    {
        Cache::forget(self::CACHE_FINANCIAL);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TaxCode>
     */
    public function taxCodesWithRates()
    {
        return Cache::rememberForever(self::CACHE_TAX_CODES, function () {
            return TaxCode::query()
                ->with(['rates', 'inputAccount', 'outputAccount'])
                ->orderBy('code')
                ->get();
        });
    }

    public function forgetTax(): void
    {
        Cache::forget(self::CACHE_TAX_CODES);
    }
}
