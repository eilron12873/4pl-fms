<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class VendorPaymentProcessedHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'vendor-payment-processed';
    }

    protected function defaultDescription(): string
    {
        return 'Vendor payment processed';
    }

    protected function defaultSourceType(): ?string
    {
        return 'vendor_payment';
    }
}

