<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class PodConfirmedHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'pod-confirmed';
    }

    protected function defaultDescription(): string
    {
        return 'POD confirmed';
    }

    protected function defaultSourceType(): ?string
    {
        return 'pod';
    }
}

