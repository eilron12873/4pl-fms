<?php

namespace App\Modules\CostingEngine\Infrastructure\Repositories;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\JournalLine;
use Illuminate\Support\Collection;

class CostingEngineRepository
{
    protected function revenueAccountPrefixes(): array
    {
        return ['41', '42', '43', '44', '45', '46'];
    }

    protected function expenseAccountPrefixes(): array
    {
        return ['51', '52', '53', '54', '55', '56', '57'];
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
        $allowed = ['shipment_id', 'route_id', 'warehouse_id', 'project_id'];
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
                'revenue' => (float) ($revenue->get($id)?->revenue ?? 0),
                'cost' => (float) ($cost->get($id)?->cost ?? 0),
            ];
        })->values();
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
