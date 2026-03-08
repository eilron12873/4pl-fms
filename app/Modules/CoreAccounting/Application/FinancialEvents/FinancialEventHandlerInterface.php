<?php

namespace App\Modules\CoreAccounting\Application\FinancialEvents;

/**
 * Pluggable handler for a single financial event type.
 * Returns the created Journal when posting succeeds, or null if event is only recorded (e.g. accepted for later processing).
 */
interface FinancialEventHandlerInterface
{
    /**
     * Normalized event type this handler supports (e.g. 'shipment-delivered', 'storage-accrual').
     */
    public function supports(string $eventType): bool;

    /**
     * Handle the event: validate payload, build journal lines, and post via JournalService.
     *
     * @param  array<string, mixed>  $payload  Validated request payload
     * @param  array{idempotency_key: string, source_system: string, source_reference: string}  $context
     * @return \App\Modules\CoreAccounting\Infrastructure\Models\Journal|null  Created journal, or null if only recorded
     */
    public function handle(array $payload, array $context): ?\App\Modules\CoreAccounting\Infrastructure\Models\Journal;
}
