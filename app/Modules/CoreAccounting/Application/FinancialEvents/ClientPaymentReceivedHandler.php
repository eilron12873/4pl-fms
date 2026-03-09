<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class ClientPaymentReceivedHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'client-payment-received';
    }

    protected function defaultDescription(): string
    {
        return 'Client payment received';
    }

    protected function defaultSourceType(): ?string
    {
        return 'client_payment';
    }
}

