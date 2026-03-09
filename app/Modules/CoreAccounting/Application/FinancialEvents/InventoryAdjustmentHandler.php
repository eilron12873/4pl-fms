<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

class InventoryAdjustmentHandler extends AbstractRuleBasedEventHandler
{
    protected function eventType(): string
    {
        return 'inventory-adjustment';
    }

    protected function defaultDescription(): string
    {
        return 'Inventory adjustment';
    }

    protected function defaultSourceType(): ?string
    {
        return 'inventory_adjustment';
    }
}

