<?php

namespace App\Modules\CostingEngine\Application;

use App\Modules\CostingEngine\Infrastructure\Models\CostingEngineSetting;

class CostingConfigService
{
    public function revenuePrefixes(): array
    {
        return $this->getArraySetting('revenue_prefixes', ['41', '42', '43', '44', '45', '46']);
    }

    public function expensePrefixes(): array
    {
        return $this->getArraySetting('expense_prefixes', ['51', '52', '53', '54', '55', '56', '57']);
    }

    public function enabledDimensions(): array
    {
        return $this->getArraySetting('enabled_dimensions', ['client_id', 'shipment_id', 'route_id', 'warehouse_id', 'project_id']);
    }

    public function functionalCurrency(): string
    {
        $arr = $this->getArraySetting('functional_currency', ['USD']);
        return strtoupper((string) ($arr[0] ?? 'USD'));
    }

    /**
     * Currency rates in form ["USD" => 1, "EUR" => 1.08]
     */
    public function fxRates(): array
    {
        $rates = $this->getArraySetting('fx_rates', ['USD' => 1.0]);
        return is_array($rates) ? $rates : ['USD' => 1.0];
    }

    public function saveSettings(array $payload): void
    {
        foreach ($payload as $key => $value) {
            CostingEngineSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }
    }

    private function getArraySetting(string $key, array $default): array
    {
        $setting = CostingEngineSetting::where('setting_key', $key)->first();
        if (! $setting) {
            return $default;
        }
        return is_array($setting->setting_value) ? $setting->setting_value : $default;
    }
}

