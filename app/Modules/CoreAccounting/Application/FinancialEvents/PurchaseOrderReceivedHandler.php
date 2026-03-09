<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class PurchaseOrderReceivedHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'purchase-order-received';
    }

    protected function defaultDescription(): string
    {
        return 'Purchase order received';
    }

    protected function defaultSourceType(): ?string
    {
        return 'purchase_order';
    }
}

