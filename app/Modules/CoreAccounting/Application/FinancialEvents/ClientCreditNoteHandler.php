<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class ClientCreditNoteHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'client-credit-note';
    }

    protected function defaultDescription(): string
    {
        return 'Client credit note';
    }

    protected function defaultSourceType(): ?string
    {
        return 'client_credit_note';
    }
}

