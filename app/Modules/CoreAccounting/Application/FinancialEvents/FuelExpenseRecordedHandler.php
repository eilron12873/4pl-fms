<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class FuelExpenseRecordedHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'fuel-expense-recorded';
    }

    protected function defaultDescription(): string
    {
        return 'Fuel expense recorded';
    }

    protected function defaultSourceType(): ?string
    {
        return 'fuel_expense';
    }
}

