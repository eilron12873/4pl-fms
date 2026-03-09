<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class AssetAcquisitionHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'asset-acquisition';
    }

    protected function defaultDescription(): string
    {
        return 'Asset acquisition';
    }

    protected function defaultSourceType(): ?string
    {
        return 'asset_acquisition';
    }
}

