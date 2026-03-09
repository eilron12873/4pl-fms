<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class ClientInvoiceIssuedHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'client-invoice-issued';
    }

    protected function defaultDescription(): string
    {
        return 'Client invoice issued';
    }

    protected function defaultSourceType(): ?string
    {
        return 'client_invoice';
    }
}

