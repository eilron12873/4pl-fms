<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class StorageDailyAccrualHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'storage-daily-accrual';
    }

    protected function defaultDescription(): string
    {
        return 'Storage daily accrual';
    }

    protected function defaultSourceType(): ?string
    {
        return 'storage_daily_accrual';
    }
}

