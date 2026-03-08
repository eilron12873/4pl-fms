<?php

namespace App\Modules\CoreAccounting\Application;

use App\Modules\CoreAccounting\Application\FinancialEvents\FinancialEventHandlerInterface;
use App\Modules\CoreAccounting\Application\FinancialEvents\ProjectMilestoneCompletedHandler;
use App\Modules\CoreAccounting\Application\FinancialEvents\ShipmentDeliveredHandler;
use App\Modules\CoreAccounting\Application\FinancialEvents\StorageAccrualHandler;
use App\Modules\CoreAccounting\Application\FinancialEvents\VendorInvoiceApprovedHandler;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use Illuminate\Contracts\Container\Container;

class FinancialEventDispatcher
{
    /** @var array<int, FinancialEventHandlerInterface> */
    protected array $handlers = [];

    public function __construct(Container $container)
    {
        $this->register($container->make(ShipmentDeliveredHandler::class));
        $this->register($container->make(StorageAccrualHandler::class));
        $this->register($container->make(VendorInvoiceApprovedHandler::class));
        $this->register($container->make(ProjectMilestoneCompletedHandler::class));
    }

    public function register(FinancialEventHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Dispatch event to the first supporting handler. If none support, record as accepted (journal_id null).
     *
     * @param  array{idempotency_key: string, source_system: string, source_reference: string}  $context
     * @return array{status: string, journal_id?: int, journal_number?: string, message?: string}
     */
    public function dispatch(string $eventType, array $payload, array $context): array
    {
        foreach ($this->handlers as $handler) {
            if (! $handler->supports($eventType)) {
                continue;
            }

            $journal = $handler->handle($payload, $context);

            if ($journal instanceof Journal) {
                return [
                    'status' => 'posted',
                    'journal_id' => $journal->id,
                    'journal_number' => $journal->journal_number,
                ];
            }
        }

        PostingSource::create([
            'journal_id' => null,
            'source_system' => $context['source_system'],
            'source_type' => $payload['source_type'] ?? null,
            'source_reference' => $context['source_reference'],
            'event_type' => $eventType,
            'idempotency_key' => $context['idempotency_key'],
            'payload' => $payload,
        ]);

        return [
            'status' => 'accepted',
            'message' => 'Event recorded for future processing.',
        ];
    }
}
