<?php

namespace App\Modules\CoreAccounting\Application;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalTemplate;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalTemplateLine;

class JournalTemplateService
{
    public function __construct(
        protected JournalService $journalService,
    ) {
    }

    /**
     * Instantiate and post a journal from the given template.
     *
     * Does not change any existing posting behavior; templates are used only
     * when explicitly invoked (e.g. from a UI or console command).
     *
     * @param  array<string, mixed>  $metaOverrides
     */
    public function postFromTemplate(JournalTemplate $template, array $metaOverrides = []): Journal
    {
        $lines = [];

        /** @var JournalTemplateLine $line */
        foreach ($template->lines as $line) {
            $lines[] = [
                'account_id' => $line->account_id,
                'description' => $line->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'client_id' => $line->client_id,
                'shipment_id' => $line->shipment_id,
                'route_id' => $line->route_id,
                'warehouse_id' => $line->warehouse_id,
                'vehicle_id' => $line->vehicle_id,
                'project_id' => $line->project_id,
                'service_line_id' => $line->service_line_id,
                'cost_center_id' => $line->cost_center_id,
            ];
        }

        $meta = array_merge([
            'description' => $template->description ?? $template->name,
        ], $metaOverrides);

        return $this->journalService->post($lines, $meta);
    }
}

