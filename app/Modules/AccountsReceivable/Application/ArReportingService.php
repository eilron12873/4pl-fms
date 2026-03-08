<?php

namespace App\Modules\AccountsReceivable\Application;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArPayment;
use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Support\Collection;

class ArReportingService
{
    /**
     * Statement of account for a client: invoices, payments, adjustments, balance.
     *
     * @return array{client: BillingClient, invoices: Collection, payments: Collection, balance: float}
     */
    public function statementOfAccount(int $clientId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $client = BillingClient::findOrFail($clientId);
        $invoices = ArInvoice::where('client_id', $clientId)
            ->with(['lines', 'adjustments', 'invoicePayments.payment'])
            ->when($fromDate, fn ($q) => $q->whereDate('invoice_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('invoice_date', '<=', $toDate))
            ->orderBy('invoice_date')
            ->get();

        $payments = $client->id ? ArPayment::where('client_id', $clientId)
            ->when($fromDate, fn ($q) => $q->whereDate('payment_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('payment_date', '<=', $toDate))
            ->orderBy('payment_date')
            ->get() : collect();

        $balance = $invoices->sum('total') - $invoices->sum('amount_allocated');
        return [
            'client' => $client,
            'invoices' => $invoices,
            'payments' => $payments,
            'balance' => round($balance, 2),
        ];
    }

    /**
     * AR aging buckets: 0-30, 31-60, 61-90, 90+ days overdue.
     *
     * @return Collection<int, array{client_id: int, client_code: string, client_name: string, current: float, days_30: float, days_60: float, days_90: float, over_90: float, total: float}>
     */
    public function agingReport(?string $asOfDate = null): Collection
    {
        $asOf = $asOfDate ? \Carbon\Carbon::parse($asOfDate) : now();
        $invoices = ArInvoice::with('client')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->get();

        $byClient = [];
        foreach ($invoices as $inv) {
            $due = $inv->due_date?->diffInDays($asOf, false) ?? 0;
            $balance = (float) $inv->total - (float) $inv->amount_allocated;
            if ($balance <= 0) {
                continue;
            }
            $cid = $inv->client_id;
            if (! isset($byClient[$cid])) {
                $byClient[$cid] = [
                    'client_id' => $cid,
                    'client_code' => $inv->client->code ?? '',
                    'client_name' => $inv->client->name ?? '',
                    'current' => 0,
                    'days_30' => 0,
                    'days_60' => 0,
                    'days_90' => 0,
                    'over_90' => 0,
                    'total' => 0,
                ];
            }
            if ($due <= 0) {
                $byClient[$cid]['current'] += $balance;
            } elseif ($due <= 30) {
                $byClient[$cid]['days_30'] += $balance;
            } elseif ($due <= 60) {
                $byClient[$cid]['days_60'] += $balance;
            } elseif ($due <= 90) {
                $byClient[$cid]['days_90'] += $balance;
            } else {
                $byClient[$cid]['over_90'] += $balance;
            }
            $byClient[$cid]['total'] += $balance;
        }

        return collect($byClient)->values()->sortByDesc('total');
    }
}
