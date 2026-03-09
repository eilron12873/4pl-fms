<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class DepreciationPostingHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'depreciation-posting';
    }

    protected function defaultDescription(): string
    {
        return 'Depreciation posting';
    }

    protected function defaultSourceType(): ?string
    {
        return 'depreciation';
    }
}

