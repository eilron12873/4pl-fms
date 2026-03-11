<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Application\CoreAccountingOverview;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\AccountImportLog;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRule;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\CoreAccounting\Infrastructure\Models\PeriodChangeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoreAccountingController extends Controller
{
    public function __construct(
        protected AuditService $audit,
        protected CoreAccountingOverview $overview,
    ) {}
    public function index(): View
    {
        return view('core-accounting::index');
    }

    public function overviewJson(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('core-accounting.view');

        return response()->json($this->overview->summary());
    }

    public function accounts(): View
    {
        $accounts = Account::with('parent')->orderBy('code')->paginate(50);
        return view('core-accounting::accounts.index', compact('accounts'));
    }

    public function accountShow(int $id): View
    {
        $account = Account::with('parent', 'children')->findOrFail($id);
        return view('core-accounting::accounts.show', compact('account'));
    }

    public function accountsCreate(): View
    {
        $this->authorize('core-accounting.manage');
        $account = new Account(['is_posting' => true, 'is_active' => true]);
        $parentOptions = Account::orderBy('code')->get();

        return view('core-accounting::accounts.form', [
            'account' => $account,
            'parentOptions' => $parentOptions,
            'mode' => 'create',
        ]);
    }

    public function accountsStore(Request $request): RedirectResponse
    {
        $this->authorize('core-accounting.manage');

        $data = $this->validateAccount($request);

        $account = Account::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'parent_id' => $data['parent_id'] ?? null,
            'level' => $data['level'] ?? 2,
            'is_posting' => $data['is_posting'] ?? false,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()
            ->route('core-accounting.accounts.show', $account->id)
            ->with('success', __('Account created.'));
    }

    public function accountsEdit(int $id): View
    {
        $this->authorize('core-accounting.manage');
        $account = Account::with('parent')->findOrFail($id);
        $parentOptions = Account::where('id', '!=', $account->id)->orderBy('code')->get();

        return view('core-accounting::accounts.form', [
            'account' => $account,
            'parentOptions' => $parentOptions,
            'mode' => 'edit',
        ]);
    }

    public function accountsUpdate(Request $request, int $id): RedirectResponse
    {
        $this->authorize('core-accounting.manage');
        $account = Account::findOrFail($id);

        $data = $this->validateAccount($request, $account->id);

        $account->update([
            // code is intentionally immutable to avoid breaking mappings
            'name' => $data['name'],
            'type' => $data['type'],
            'parent_id' => $data['parent_id'] ?? null,
            'level' => $data['level'] ?? $account->level,
            'is_posting' => $data['is_posting'] ?? false,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()
            ->route('core-accounting.accounts.show', $account->id)
            ->with('success', __('Account updated.'));
    }

    public function accountsDeactivate(Request $request, int $id): RedirectResponse
    {
        $this->authorize('core-accounting.manage');
        $account = Account::findOrFail($id);

        $account->update(['is_active' => false]);

        return redirect()
            ->route('core-accounting.accounts.index')
            ->with('success', __('Account deactivated. It will no longer be used for new postings.'));
    }

    public function accountsImportTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('core-accounting.manage');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="lfs_chart_of_accounts_template.csv"',
        ];

        $callback = function () {
            $output = fopen('php://output', 'w');
            // Header row
            fputcsv($output, ['code', 'name', 'type', 'parent_code', 'level', 'is_posting', 'is_active']);
            // Example rows
            fputcsv($output, ['4100', 'Warehousing Revenue', 'revenue', '', '2', '1', '1']);
            fputcsv($output, ['4110', 'Pallet Storage Revenue', 'revenue', '4100', '3', '1', '1']);
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function accountsImport(Request $request): RedirectResponse
    {
        $this->authorize('core-accounting.manage');

        $request->validate([
            'accounts_csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('accounts_csv');
        $contents = (string) file_get_contents($file->getRealPath());
        $hash = hash('sha256', $contents);

        // Prevent duplicate import of the same file
        if (AccountImportLog::where('file_hash', $hash)->exists()) {
            return redirect()
                ->route('core-accounting.accounts.index')
                ->with('error', __('This file has already been imported.'));
        }

        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return redirect()
                ->route('core-accounting.accounts.index')
                ->with('error', __('Unable to read uploaded file.'));
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return redirect()
                ->route('core-accounting.accounts.index')
                ->with('error', __('CSV file is empty or invalid.'));
        }

        $normalizedHeader = array_map('strtolower', $header);
        $requiredColumns = ['code', 'name', 'type'];
        foreach ($requiredColumns as $col) {
            if (! in_array($col, $normalizedHeader, true)) {
                fclose($handle);
                return redirect()
                    ->route('core-accounting.accounts.index')
                    ->with('error', __('CSV must contain columns: :cols', ['cols' => implode(', ', $requiredColumns)]));
            }
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $row = array_combine($normalizedHeader, $data);
            if ($row !== false) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (empty($rows)) {
            return redirect()
                ->route('core-accounting.accounts.index')
                ->with('error', __('No data rows found in CSV.'));
        }

        // Validation: duplicates, types, parents, and cycle prevention
        $errors = [];
        $seenCodes = [];
        $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // account for header
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $parentCode = trim((string) ($row['parent_code'] ?? ''));

            if ($code === '' || $name === '' || $type === '') {
                $errors[] = "Line {$line}: code, name and type are required.";
                continue;
            }
            if (isset($seenCodes[$code])) {
                $errors[] = "Line {$line}: duplicate code '{$code}' within file.";
            } else {
                $seenCodes[$code] = true;
            }
            if (! in_array($type, $validTypes, true)) {
                $errors[] = "Line {$line}: invalid account type '{$type}'.";
            }
            if ($parentCode !== '' && $parentCode === $code) {
                $errors[] = "Line {$line}: parent_code cannot be the same as code.";
            }
        }

        if (! empty($errors)) {
            return redirect()
                ->route('core-accounting.accounts.index')
                ->with('error', implode(' ', $errors));
        }

        // Build in-memory map of all prospective accounts (existing + new)
        $allAccountsByCode = Account::query()
            ->get()
            ->keyBy('code')
            ->toArray();

        foreach ($rows as $row) {
            $code = trim((string) $row['code']);
            if (! isset($allAccountsByCode[$code])) {
                $allAccountsByCode[$code] = [
                    'code' => $code,
                    'name' => trim((string) $row['name']),
                    'type' => strtolower(trim((string) $row['type'])),
                    'parent_code' => trim((string) ($row['parent_code'] ?? '')),
                    'level' => (int) ($row['level'] ?? 2),
                    'is_posting' => ! empty($row['is_posting']),
                    'is_active' => ! array_key_exists('is_active', $row) || ! in_array(strtolower((string) $row['is_active']), ['0', 'false', 'no'], true),
                ];
            }
        }

        // Cycle detection for parent relationships using DFS
        $graph = [];
        foreach ($allAccountsByCode as $code => $data) {
            $parentCode = $data['parent_code'] ?? '';
            if ($parentCode !== '' && isset($allAccountsByCode[$parentCode])) {
                $graph[$code] = $parentCode;
            }
        }

        $visiting = [];
        $visited = [];
        $cycleFound = false;

        $dfs = function (string $code) use (&$dfs, &$graph, &$visiting, &$visited, &$cycleFound): void {
            if ($cycleFound) {
                return;
            }
            if (isset($visiting[$code])) {
                $cycleFound = true;
                return;
            }
            if (isset($visited[$code])) {
                return;
            }
            $visiting[$code] = true;
            if (isset($graph[$code])) {
                $dfs($graph[$code]);
            }
            unset($visiting[$code]);
            $visited[$code] = true;
        };

        foreach (array_keys($allAccountsByCode) as $code) {
            $dfs($code);
            if ($cycleFound) {
                break;
            }
        }

        if ($cycleFound) {
            return redirect()
                ->route('core-accounting.accounts.index')
                ->with('error', __('Import aborted: detected a cycle in parent/child account relationships.'));
        }

        // Create accounts in parent-first order
        $created = 0;
        foreach ($rows as $row) {
            $code = trim((string) $row['code']);
            $name = trim((string) $row['name']);
            $type = strtolower(trim((string) $row['type']));
            $parentCode = trim((string) ($row['parent_code'] ?? ''));
            $level = (int) ($row['level'] ?? 2);
            $isPosting = ! empty($row['is_posting']);
            $isActive = ! array_key_exists('is_active', $row) || ! in_array(strtolower((string) $row['is_active']), ['0', 'false', 'no'], true);

            if (Account::where('code', $code)->exists()) {
                continue;
            }

            $parentId = null;
            if ($parentCode !== '') {
                $parent = Account::where('code', $parentCode)->first();
                if (! $parent) {
                    // Create parent skeleton if it was also in the file and not yet saved
                    if (isset($allAccountsByCode[$parentCode])) {
                        $parent = Account::firstOrCreate(
                            ['code' => $parentCode],
                            [
                                'name' => $allAccountsByCode[$parentCode]['name'],
                                'type' => $allAccountsByCode[$parentCode]['type'],
                                'level' => $allAccountsByCode[$parentCode]['level'],
                                'is_posting' => $allAccountsByCode[$parentCode]['is_posting'],
                                'is_active' => $allAccountsByCode[$parentCode]['is_active'],
                            ],
                        );
                    }
                }
                $parentId = $parent?->id;
            }

            Account::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentId,
                'level' => $level,
                'is_posting' => $isPosting,
                'is_active' => $isActive,
            ]);

            $created++;
        }

        AccountImportLog::create([
            'file_hash' => $hash,
            'original_name' => $file->getClientOriginalName(),
            'user_id' => $request->user()?->id,
            'rows_imported' => $created,
        ]);

        return redirect()
            ->route('core-accounting.accounts.index')
            ->with('success', __('Accounts imported: :count new accounts created.', ['count' => $created]));
    }

    public function accountsExport(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('core-accounting.view');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="lfs_chart_of_accounts_export.csv"',
        ];

        $callback = function () {
            $output = fopen('php://output', 'w');
            // Header row consistent with import template
            fputcsv($output, ['code', 'name', 'type', 'parent_code', 'level', 'is_posting', 'is_active']);

            Account::with('parent')
                ->orderBy('code')
                ->chunk(200, function ($chunk) use ($output) {
                    foreach ($chunk as $account) {
                        fputcsv($output, [
                            $account->code,
                            $account->name,
                            $account->type,
                            $account->parent?->code,
                            $account->level,
                            $account->is_posting ? 1 : 0,
                            ($account->is_active ?? true) ? 1 : 0,
                        ]);
                    }
                });

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAccount(Request $request, ?int $accountId = null): array
    {
        $uniqueCode = 'unique:accounts,code';
        if ($accountId) {
            $uniqueCode .= ',' . $accountId;
        }

        return $request->validate([
            'code' => $accountId ? ['required', 'string', 'max:50'] : ['required', 'string', 'max:50', $uniqueCode],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'level' => ['nullable', 'integer', 'min:1', 'max:9'],
            'is_posting' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    public function journals(): View
    {
        $journals = Journal::withCount('lines')->orderByDesc('journal_date')->orderByDesc('id')->paginate(20);
        return view('core-accounting::journals.index', compact('journals'));
    }

    public function journalShow(int $id): View
    {
        $journal = Journal::with(['lines.account', 'postingSource', 'reversalLinkAsOriginal.reversal', 'reversalLinkAsReversal.original'])
            ->findOrFail($id);
        return view('core-accounting::journals.show', compact('journal'));
    }

    public function postingSources(): View
    {
        $sources = PostingSource::with('journal')->orderByDesc('id')->paginate(30);
        return view('core-accounting::posting-sources.index', compact('sources'));
    }

    public function periods(): View
    {
        $periods = Period::orderByDesc('start_date')->paginate(24);
        return view('core-accounting::periods.index', compact('periods'));
    }

    public function closePeriod(Request $request, int $id): RedirectResponse
    {
        $this->authorize('core-accounting.manage');
        $period = Period::findOrFail($id);
        if ($period->isClosed()) {
            return redirect()->route('core-accounting.periods.index')->with('error', __('Period is already closed.'));
        }
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        PeriodChangeLog::create([
            'period_id' => $period->id,
            'action' => 'closed',
            'user_id' => $request->user()?->id,
        ]);
        $this->audit->logFinancial(
            "Period closed: {$period->code} ({$period->start_date?->toDateString()} to {$period->end_date?->toDateString()})",
            $period,
            ['period_code' => $period->code],
            'period.closed',
        );
        return redirect()->route('core-accounting.periods.index')->with('success', __('Period closed.'));
    }

    public function postingRules(): View
    {
        $rules = PostingRule::withCount('lines')->orderBy('event_type')->paginate(20);

        return view('core-accounting::posting-rules.index', compact('rules'));
    }

    public function postingRulesCreate(): View
    {
        $accounts = Account::where('is_posting', true)->orderBy('code')->get();

        return view('core-accounting::posting-rules.form', [
            'rule' => new PostingRule(),
            'accounts' => $accounts,
            'mode' => 'create',
        ]);
    }

    public function postingRulesStore(Request $request): RedirectResponse
    {
        $data = $this->validatePostingRule($request);

        $rule = PostingRule::create([
            'event_type' => $data['event_type'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        $this->syncPostingRuleLines($rule, $data['lines'] ?? []);
        $this->syncPostingRuleConditions($rule, $data['conditions'] ?? []);

        return redirect()
            ->route('core-accounting.posting-rules.index')
            ->with('success', __('Posting rule created.'));
    }

    public function postingRulesEdit(int $id): View
    {
        $rule = PostingRule::with('lines.account')->findOrFail($id);
        $accounts = Account::where('is_posting', true)->orderBy('code')->get();

        return view('core-accounting::posting-rules.form', [
            'rule' => $rule,
            'accounts' => $accounts,
            'mode' => 'edit',
        ]);
    }

    public function postingRulesUpdate(Request $request, int $id): RedirectResponse
    {
        $rule = PostingRule::findOrFail($id);
        $data = $this->validatePostingRule($request, $rule->id);

        $rule->update([
            'event_type' => $data['event_type'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        $this->syncPostingRuleLines($rule, $data['lines'] ?? []);
        $this->syncPostingRuleConditions($rule, $data['conditions'] ?? []);

        return redirect()
            ->route('core-accounting.posting-rules.index')
            ->with('success', __('Posting rule updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePostingRule(Request $request, ?int $ruleId = null): array
    {
        $uniqueRule = 'unique:posting_rules,event_type';
        if ($ruleId) {
            $uniqueRule .= ',' . $ruleId;
        }

        return $request->validate([
            'event_type' => ['required', 'string', 'max:255', $uniqueRule],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'lines' => ['nullable', 'array'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.entry_type' => ['required', 'in:debit,credit'],
            'lines.*.amount_source' => ['required', 'string', 'max:255'],
            'lines.*.resolver_type' => ['nullable', 'string', 'max:255'],
            'lines.*.map_client_id' => ['sometimes', 'boolean'],
            'lines.*.map_shipment_id' => ['sometimes', 'boolean'],
            'lines.*.map_route_id' => ['sometimes', 'boolean'],
            'lines.*.map_warehouse_id' => ['sometimes', 'boolean'],
            'lines.*.map_vehicle_id' => ['sometimes', 'boolean'],
            'lines.*.map_project_id' => ['sometimes', 'boolean'],
            'lines.*.map_service_line_id' => ['sometimes', 'boolean'],
            'lines.*.map_cost_center_id' => ['sometimes', 'boolean'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field_name' => ['nullable', 'string', 'max:255'],
            'conditions.*.operator' => ['nullable', 'string', 'max:16'],
            'conditions.*.comparison_value' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $linesInput
     */
    protected function syncPostingRuleLines(PostingRule $rule, array $linesInput): void
    {
        $rule->lines()->delete();

        $sequence = 1;
        foreach ($linesInput as $line) {
            if (empty($line['account_id']) || empty($line['entry_type']) || empty($line['amount_source'])) {
                continue;
            }

            $dimensionSource = [];

            if (! empty($line['map_client_id'])) {
                $dimensionSource['client_id'] = 'payload.client_id';
            }
            if (! empty($line['map_shipment_id'])) {
                $dimensionSource['shipment_id'] = 'payload.shipment_id';
            }
            if (! empty($line['map_route_id'])) {
                $dimensionSource['route_id'] = 'payload.route_id';
            }
            if (! empty($line['map_warehouse_id'])) {
                $dimensionSource['warehouse_id'] = 'payload.warehouse_id';
            }
            if (! empty($line['map_vehicle_id'])) {
                $dimensionSource['vehicle_id'] = 'payload.vehicle_id';
            }
            if (! empty($line['map_project_id'])) {
                $dimensionSource['project_id'] = 'payload.project_id';
            }
            if (! empty($line['map_service_line_id'])) {
                $dimensionSource['service_line_id'] = 'payload.service_line_id';
            }
            if (! empty($line['map_cost_center_id'])) {
                $dimensionSource['cost_center_id'] = 'payload.cost_center_id';
            }

            $rule->lines()->create([
                'account_id' => $line['account_id'],
                'entry_type' => $line['entry_type'],
                'amount_source' => $line['amount_source'],
                'resolver_type' => $line['resolver_type'] ?? null,
                'dimension_source' => $dimensionSource ?: null,
                'sequence' => $sequence++,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditionsInput
     */
    protected function syncPostingRuleConditions(PostingRule $rule, array $conditionsInput): void
    {
        $rule->conditions()->delete();

        foreach ($conditionsInput as $condition) {
            $field = $condition['field_name'] ?? null;
            $operator = $condition['operator'] ?? null;
            $value = $condition['comparison_value'] ?? null;

            if ($field === null || $field === '' || $operator === null || $operator === '' || $value === null || $value === '') {
                continue;
            }

            $rule->conditions()->create([
                'field_name' => $field,
                'operator' => $operator,
                'comparison_value' => $value,
                'priority' => 100,
            ]);
        }
    }
}

