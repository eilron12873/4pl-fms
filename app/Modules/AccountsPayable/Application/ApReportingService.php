<?php

namespace App\Modules\AccountsPayable\Application;

use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use Illuminate\Support\Collection;

class ApReportingService
{
    public function statementOfAccount(int $vendorId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $vendor = Vendor::findOrFail($vendorId);
        $bills = ApBill::where('vendor_id', $vendorId)
            ->with(['lines', 'adjustments', 'billPayments.payment'])
            ->when($fromDate, fn ($q) => $q->whereDate('bill_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('bill_date', '<=', $toDate))
            ->orderBy('bill_date')
            ->get();

        $payments = ApPayment::where('vendor_id', $vendorId)
            ->when($fromDate, fn ($q) => $q->whereDate('payment_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('payment_date', '<=', $toDate))
            ->orderBy('payment_date')
            ->get();

        $balance = $bills->sum('total') - $bills->sum('amount_allocated');
        return [
            'vendor' => $vendor,
            'bills' => $bills,
            'payments' => $payments,
            'balance' => round($balance, 2),
        ];
    }

    public function agingReport(?string $asOfDate = null): Collection
    {
        $asOf = $asOfDate ? \Carbon\Carbon::parse($asOfDate) : now();
        $bills = ApBill::with('vendor')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->get();

        $byVendor = [];
        foreach ($bills as $bill) {
            $due = $bill->due_date?->diffInDays($asOf, false) ?? 0;
            $balance = (float) $bill->total - (float) $bill->amount_allocated;
            if ($balance <= 0) {
                continue;
            }
            $vid = $bill->vendor_id;
            if (! isset($byVendor[$vid])) {
                $byVendor[$vid] = [
                    'vendor_id' => $vid,
                    'vendor_code' => $bill->vendor->code ?? '',
                    'vendor_name' => $bill->vendor->name ?? '',
                    'current' => 0,
                    'days_30' => 0,
                    'days_60' => 0,
                    'days_90' => 0,
                    'over_90' => 0,
                    'total' => 0,
                ];
            }
            if ($due <= 0) {
                $byVendor[$vid]['current'] += $balance;
            } elseif ($due <= 30) {
                $byVendor[$vid]['days_30'] += $balance;
            } elseif ($due <= 60) {
                $byVendor[$vid]['days_60'] += $balance;
            } elseif ($due <= 90) {
                $byVendor[$vid]['days_90'] += $balance;
            } else {
                $byVendor[$vid]['over_90'] += $balance;
            }
            $byVendor[$vid]['total'] += $balance;
        }

        return collect($byVendor)->values()->sortByDesc('total');
    }
}
