<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class FreightCostAccrualHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'freight-cost-accrual';
    }

    protected function defaultDescription(): string
    {
        return 'Freight cost accrual';
    }

    protected function defaultSourceType(): ?string
    {
        return 'freight_cost_accrual';
    }
}

