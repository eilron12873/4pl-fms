<?php

namespace App\Modules\CostingEngine\Infrastructure\Repositories;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use App\Modules\CostingEngine\Application\CostingConfigService;
use Illuminate\Support\Collection;

class CostingEngineRepository
{
    public function __construct(
        protected CostingConfigService $configService
    ) {
    }

    protected function revenueAccountPrefixes(): array
    {
        return $this->configService->revenuePrefixes();
    }

    protected function expenseAccountPrefixes(): array
    {
        return $this->configService->expensePrefixes();
    }

    /**
     * Revenue by client from AR invoices (issued, partially_paid, paid).
     *
     * @return Collection<int, float> keyed by client_id
     */
    public function revenueByClientFromInvoices(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = ArInvoice::query()
            ->whereIn('status', ['issued', 'partially_paid', 'paid'])
            ->selectRaw('client_id, SUM(total) as total_revenue')
            ->groupBy('client_id');

        if ($fromDate) {
            $query->whereDate('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('invoice_date', '<=', $toDate);
        }

        return $query->get()->pluck('total_revenue', 'client_id')->map(fn ($v) => (float) $v);
    }

    /**
     * Cost by client from posted journal lines (expense account debits where client_id set).
     *
     * @return Collection<int, float> keyed by client_id
     */
    public function costByClientFromJournalLines(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $expenseAccountIds = $this->accountIdsByPrefixes($this->expenseAccountPrefixes());
        if ($expenseAccountIds->isEmpty()) {
            return collect();
        }

        $query = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereNotNull('journal_lines.client_id')
            ->whereIn('journal_lines.account_id', $expenseAccountIds)
            ->selectRaw('journal_lines.client_id, SUM(journal_lines.debit) as total_cost')
            ->groupBy('journal_lines.client_id');

        if ($fromDate) {
            $query->whereDate('journals.journal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('journals.journal_date', '<=', $toDate);
        }

        return $query->get()->pluck('total_cost', 'client_id')->map(fn ($v) => (float) $v);
    }

    /**
     * Revenue and cost by a dimension column (e.g. warehouse_id, shipment_id) from journal lines.
     * Revenue = sum(credit) for revenue accounts; cost = sum(debit) for expense accounts.
     *
     * @return Collection<int, array{string: int|float}> each item has dimension key (e.g. warehouse_id), revenue, cost
     */
    public function revenueAndCostByDimension(string $dimensionColumn, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $allowed = array_values(array_intersect(
            ['shipment_id', 'route_id', 'warehouse_id', 'project_id'],
            $this->configService->enabledDimensions()
        ));
        if (! in_array($dimensionColumn, $allowed, true)) {
            return collect();
        }

        $revenueAccountIds = $this->accountIdsByPrefixes($this->revenueAccountPrefixes());
        $expenseAccountIds = $this->accountIdsByPrefixes($this->expenseAccountPrefixes());

        $base = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereNotNull('journal_lines.' . $dimensionColumn);

        if ($fromDate) {
            $base->whereDate('journals.journal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $base->whereDate('journals.journal_date', '<=', $toDate);
        }

        $revenue = (clone $base)
            ->whereIn('journal_lines.account_id', $revenueAccountIds)
            ->selectRaw('journal_lines.' . $dimensionColumn . ' as dim_id, SUM(journal_lines.credit) as revenue')
            ->groupBy('journal_lines.' . $dimensionColumn)
            ->get()
            ->keyBy('dim_id');

        $cost = (clone $base)
            ->whereIn('journal_lines.account_id', $expenseAccountIds)
            ->selectRaw('journal_lines.' . $dimensionColumn . ' as dim_id, SUM(journal_lines.debit) as cost')
            ->groupBy('journal_lines.' . $dimensionColumn)
            ->get()
            ->keyBy('dim_id');

        $dimIds = $revenue->keys()->merge($cost->keys())->unique();

        return $dimIds->map(function ($id) use ($dimensionColumn, $revenue, $cost) {
            return [
                $dimensionColumn => $id,
                'revenue' => round((float) ($revenue->get($id)?->revenue ?? 0), 2),
                'cost' => round((float) ($cost->get($id)?->cost ?? 0), 2),
            ];
        })->values();
    }

    public function journalLinesByDimension(
        string $dimensionColumn,
        int|string $dimensionId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): Collection {
        $allowed = ['client_id', 'shipment_id', 'route_id', 'warehouse_id', 'project_id'];
        if (! in_array($dimensionColumn, $allowed, true)) {
            return collect();
        }

        $query = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journals.status', 'posted')
            ->where('journal_lines.' . $dimensionColumn, $dimensionId)
            ->selectRaw('journal_lines.id, journal_lines.journal_id, journals.journal_number, journals.journal_date, accounts.code as account_code, accounts.name as account_name, journal_lines.description, journal_lines.debit, journal_lines.credit');

        if ($fromDate) {
            $query->whereDate('journals.journal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('journals.journal_date', '<=', $toDate);
        }

        return $query->orderByDesc('journals.journal_date')->limit(200)->get();
    }

    public function arInvoicesByClient(int|string $clientId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = ArInvoice::query()
            ->where('client_id', $clientId)
            ->whereIn('status', ['issued', 'partially_paid', 'paid'])
            ->select(['id', 'invoice_number', 'invoice_date', 'due_date', 'status', 'total', 'currency']);

        if ($fromDate) {
            $query->whereDate('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('invoice_date', '<=', $toDate);
        }

        return $query->orderByDesc('invoice_date')->limit(200)->get();
    }

    protected function accountIdsByPrefixes(array $prefixes): Collection
    {
        if (empty($prefixes)) {
            return collect();
        }

        return Account::query()
            ->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('code', 'like', $prefix . '%');
                }
            })
            ->pluck('id');
    }
}
