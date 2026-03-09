<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Application\CoreAccountingOverview;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
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

