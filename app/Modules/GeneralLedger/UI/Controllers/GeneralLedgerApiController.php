<?php

namespace App\Modules\GeneralLedger\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\GeneralLedger\Application\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralLedgerApiController extends Controller
{
    public function __construct(
        protected ReportingService $reporting,
    ) {
    }

    /**
     * GET /api/general-ledger/trial-balance?from_date=Y-m-d&to_date=Y-m-d
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $fromDate = $request->string('from_date')->toString() ?: null;
        $toDate = $request->string('to_date')->toString() ?: null;

        $rows = $this->reporting->trialBalance($fromDate, $toDate);

        $data = $rows->map(function ($row) {
            return [
                'account_code' => $row['account']->code,
                'account_name' => $row['account']->name,
                'debit' => round($row['debit'], 2),
                'credit' => round($row['credit'], 2),
                'balance' => round($row['balance'], 2),
            ];
        });

        return response()->json([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'rows' => $data,
        ]);
    }

    /**
     * GET /api/general-ledger/ledger?account_id=1&from_date=Y-m-d&to_date=Y-m-d&per_page=50
     */
    public function ledger(Request $request): JsonResponse
    {
        $accountId = $request->integer('account_id') ?: null;
        $fromDate = $request->string('from_date')->toString() ?: null;
        $toDate = $request->string('to_date')->toString() ?: null;
        $perPage = min($request->integer('per_page', 50), 100);

        $result = $this->reporting->generalLedger($accountId, $fromDate, $toDate, $perPage);

        if (! $result['account']) {
            return response()->json([
                'account' => null,
                'lines' => [],
                'opening_balance' => 0,
            ]);
        }

        $lines = $result['lines']->map(fn ($line) => [
            'journal_id' => $line['journal_id'],
            'date' => $line['date']?->format('Y-m-d'),
            'journal_number' => $line['journal_number'],
            'description' => $line['description'],
            'debit' => round($line['debit'], 2),
            'credit' => round($line['credit'], 2),
            'balance' => round($line['balance'], 2),
        ]);

        return response()->json([
            'account' => [
                'id' => $result['account']->id,
                'code' => $result['account']->code,
                'name' => $result['account']->name,
            ],
            'opening_balance' => round($result['opening_balance'] ?? 0, 2),
            'lines' => $lines,
            'pagination' => $result['paginator'] ? [
                'current_page' => $result['paginator']->currentPage(),
                'per_page' => $result['paginator']->perPage(),
                'total' => $result['paginator']->total(),
            ] : null,
        ]);
    }

    /**
     * GET /api/general-ledger/accounts - list accounts for dropdowns
     */
    public function accounts(): JsonResponse
    {
        $accounts = Account::query()->orderBy('code')->get(['id', 'code', 'name', 'type']);

        return response()->json(['accounts' => $accounts]);
    }
}
