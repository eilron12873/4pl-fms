<?php

namespace App\Modules\AccountsPayable\Application;

use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\ApPayment;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * High-level AP KPIs as of a given date.
     *
     * - totalOutstanding: sum of unpaid AP balances
     * - dpo: Days Payable Outstanding approximation
     * - avgDaysToPay: average days between bill date and payment date (for closed bills)
     * - topVendors: top vendors by outstanding balance
     */
    public function kpis(?string $asOfDate = null, int $topVendorsLimit = 5): array
    {
        $asOf = $asOfDate ? \Carbon\Carbon::parse($asOfDate) : now();

        $openBills = ApBill::with('vendor')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->get();

        $totalOutstanding = 0.0;
        $apAmountWeightedAge = 0.0;

        $perVendorOutstanding = [];

        foreach ($openBills as $bill) {
            $balance = (float) $bill->total - (float) $bill->amount_allocated;
            if ($balance <= 0) {
                continue;
            }

            $totalOutstanding += $balance;

            $ageDays = $bill->bill_date?->diffInDays($asOf, false) ?? 0;
            if ($ageDays < 0) {
                $ageDays = 0;
            }
            $apAmountWeightedAge += $balance * $ageDays;

            $vid = $bill->vendor_id;
            if (! isset($perVendorOutstanding[$vid])) {
                $perVendorOutstanding[$vid] = [
                    'vendor_id' => $vid,
                    'vendor_code' => $bill->vendor->code ?? '',
                    'vendor_name' => $bill->vendor->name ?? '',
                    'outstanding' => 0.0,
                ];
            }
            $perVendorOutstanding[$vid]['outstanding'] += $balance;
        }

        $dpo = 0.0;
        if ($totalOutstanding > 0) {
            // Approximate DPO as weighted age of open payables.
            $dpo = round($apAmountWeightedAge / $totalOutstanding, 1);
        }

        $avgDaysToPay = $this->calculateAverageDaysToPay();

        $topVendors = collect($perVendorOutstanding)
            ->sortByDesc('outstanding')
            ->take($topVendorsLimit)
            ->values()
            ->all();

        return [
            'as_of_date' => $asOf->toDateString(),
            'total_outstanding' => round($totalOutstanding, 2),
            'dpo' => $dpo,
            'avg_days_to_pay' => $avgDaysToPay,
            'top_vendors' => $topVendors,
        ];
    }

    /**
     * Calculate average days between bill date and full payment for fully paid bills.
     */
    protected function calculateAverageDaysToPay(): float
    {
        $rows = DB::table('ap_bills')
            ->join('ap_bill_payments', 'ap_bill_payments.bill_id', '=', 'ap_bills.id')
            ->join('ap_payments', 'ap_bill_payments.payment_id', '=', 'ap_payments.id')
            ->where('ap_bills.status', 'paid')
            ->select(
                'ap_bills.id as bill_id',
                'ap_bills.bill_date',
                DB::raw('MAX(ap_payments.payment_date) as last_payment_date')
            )
            ->groupBy('ap_bills.id', 'ap_bills.bill_date')
            ->limit(500)
            ->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $totalDays = 0.0;
        $count = 0;
        foreach ($rows as $row) {
            $billDate = $row->bill_date ? \Carbon\Carbon::parse($row->bill_date) : null;
            $lastPaymentDate = $row->last_payment_date ? \Carbon\Carbon::parse($row->last_payment_date) : null;
            if (! $billDate || ! $lastPaymentDate) {
                continue;
            }
            $days = $billDate->diffInDays($lastPaymentDate, false);
            if ($days < 0) {
                continue;
            }
            $totalDays += $days;
            $count++;
        }

        if ($count === 0) {
            return 0.0;
        }

        return round($totalDays / $count, 1);
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
