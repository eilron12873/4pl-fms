<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Core\Services\AuditService;
use App\Core\Services\SystemSettingsService;
use App\Http\Controllers\Controller;
use App\Models\TaxCode;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\Intl\Currencies;

class SystemSettingsController extends Controller
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected AuditService $audit,
    ) {}

    public function company(): View
    {
        $settings = $this->settings->general();
        $canManage = auth()->user()?->can('lfs-administration.manage') ?? false;
        $timezones = collect(timezone_identifiers_list())
            ->groupBy(fn (string $tz) => str_contains($tz, '/') ? explode('/', $tz, 2)[0] : 'Other')
            ->sortKeys();

        return view('lfs-administration::settings.company', compact('settings', 'canManage', 'timezones'));
    }

    public function companyUpdate(Request $request): RedirectResponse
    {
        $this->authorize('lfs-administration.manage');

        $request->merge([
            'default_currency' => strtoupper(trim((string) $request->input('default_currency', ''))),
        ]);

        $currencyCodes = array_keys(Currencies::getNames());

        $companyNameRules = ['string', 'max:255'];
        if (config('company.require_company_name', true)) {
            array_unshift($companyNameRules, 'required');
        } else {
            array_unshift($companyNameRules, 'nullable');
        }

        $validator = Validator::make($request->all(), [
            'company_name' => $companyNameRules,
            'company_address' => ['nullable', 'string', 'max:2000'],
            'telephone_number' => ['nullable', 'string', 'max:64'],
            'email_address' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail(__('The website must be a valid URL.'));

                    return;
                }
                $scheme = parse_url((string) $value, PHP_URL_SCHEME);
                if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
                    $fail(__('The website must use http or https.'));
                }
            }],
            'default_timezone' => ['required', 'timezone'],
            'default_date_format' => ['required', 'string', 'max:32'],
            'default_currency' => ['required', 'string', 'size:3', Rule::in($currencyCodes)],
            'registration_number' => ['nullable', 'string', 'max:128'],
            'fiscal_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'fiscal_year_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'company_logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($request): void {
            $month = $request->input('fiscal_year_start_month');
            $day = $request->input('fiscal_year_start_day');
            $monthEmpty = $month === null || $month === '';
            $dayEmpty = $day === null || $day === '';
            if ($monthEmpty && $dayEmpty) {
                return;
            }
            if ($monthEmpty || $dayEmpty) {
                $v->errors()->add(
                    'fiscal_year_start_month',
                    __('When setting a fiscal year start, both month and day are required.'),
                );

                return;
            }
            if (! checkdate((int) $month, (int) $day, 2024)) {
                $v->errors()->add(
                    'fiscal_year_start_day',
                    __('The fiscal year start is not a valid calendar date for that month.'),
                );
            }
        });

        $data = $validator->validate();
        $removeLogo = $request->boolean('remove_logo');
        $logoFile = $request->file('company_logo');

        unset($data['remove_logo'], $data['company_logo']);

        $model = $this->settings->general();
        $auditKeys = array_merge(array_keys($data), ['company_logo']);
        $before = $model->only($auditKeys);

        $disk = Storage::disk('public');

        if ($logoFile) {
            if ($model->company_logo) {
                $disk->delete($model->company_logo);
            }
            $data['company_logo'] = $logoFile->store('company', 'public');
        } elseif ($removeLogo) {
            if ($model->company_logo) {
                $disk->delete($model->company_logo);
            }
            $data['company_logo'] = null;
        }

        $model->update($data);
        $model->refresh();
        $after = $model->only($auditKeys);

        $this->settings->forgetGeneral();
        $this->logConfigurationChange(
            'settings.company.updated',
            __('Company settings updated'),
            $before,
            $after,
        );

        return redirect()->route('lfs-administration.settings.company')->with('success', __('Company settings saved.'));
    }

    public function financialControls(): View
    {
        $controls = $this->settings->financialControls();
        $canManage = auth()->user()?->can('lfs-administration.manage') ?? false;

        return view('lfs-administration::settings.financial-controls', compact('controls', 'canManage'));
    }

    public function financialControlsUpdate(Request $request): RedirectResponse
    {
        $this->authorize('lfs-administration.manage');

        $request->validate([
            'max_backdating_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $model = $this->settings->financialControls();
        $before = [
            'max_backdating_days' => $model->max_backdating_days,
            'allow_manual_journals' => $model->allow_manual_journals,
        ];

        $model->update([
            'max_backdating_days' => $request->filled('max_backdating_days')
                ? (int) $request->input('max_backdating_days')
                : null,
            'allow_manual_journals' => $request->boolean('allow_manual_journals'),
        ]);
        $model->refresh();
        $after = [
            'max_backdating_days' => $model->max_backdating_days,
            'allow_manual_journals' => $model->allow_manual_journals,
        ];

        $this->settings->forgetFinancial();
        $this->logConfigurationChange(
            'settings.financial_controls.updated',
            __('Financial controls updated'),
            $before,
            $after,
        );

        return redirect()->route('lfs-administration.settings.financial-controls')->with('success', __('Financial controls saved.'));
    }

    public function taxIndex(): View
    {
        $taxCodes = $this->settings->taxCodesWithRates();
        $canManage = auth()->user()?->can('lfs-administration.manage') ?? false;
        $accounts = Account::query()->where('is_active', true)->where('is_posting', true)->orderBy('code')->get();

        return view('lfs-administration::settings.tax-index', compact('taxCodes', 'canManage', 'accounts'));
    }

    public function taxCodeCreate(): View
    {
        $this->authorize('lfs-administration.manage');
        $accounts = Account::query()->where('is_active', true)->where('is_posting', true)->orderBy('code')->get();

        return view('lfs-administration::settings.tax-code-form', [
            'taxCode' => new TaxCode,
            'accounts' => $accounts,
            'mode' => 'create',
        ]);
    }

    public function taxCodeStore(Request $request): RedirectResponse
    {
        $this->authorize('lfs-administration.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:tax_codes,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:vat,wht,other'],
            'is_inclusive' => ['sometimes', 'boolean'],
            'rounding_mode' => ['nullable', 'string', 'max:32'],
            'input_account_id' => ['nullable', 'exists:accounts,id'],
            'output_account_id' => ['nullable', 'exists:accounts,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_inclusive'] = $request->boolean('is_inclusive');
        $data['is_active'] = $request->boolean('is_active', true);

        $code = TaxCode::create($data);
        $this->settings->forgetTax();

        $this->audit->log(
            description: __('Tax code created: :code', ['code' => $code->code]),
            event: 'settings.tax_code.created',
            subject: $code,
            properties: [
                'group' => 'tax_configuration',
                'after' => $code->only(array_keys($data)),
            ],
            logName: AuditService::LOG_CONFIGURATION,
        );

        return redirect()->route('lfs-administration.settings.tax')->with('success', __('Tax code created.'));
    }

    public function taxCodeEdit(TaxCode $taxCode): View
    {
        $this->authorize('lfs-administration.manage');
        $accounts = Account::query()->where('is_active', true)->where('is_posting', true)->orderBy('code')->get();

        return view('lfs-administration::settings.tax-code-form', [
            'taxCode' => $taxCode,
            'accounts' => $accounts,
            'mode' => 'edit',
        ]);
    }

    public function taxCodeUpdate(Request $request, TaxCode $taxCode): RedirectResponse
    {
        $this->authorize('lfs-administration.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:tax_codes,code,'.$taxCode->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:vat,wht,other'],
            'is_inclusive' => ['sometimes', 'boolean'],
            'rounding_mode' => ['nullable', 'string', 'max:32'],
            'input_account_id' => ['nullable', 'exists:accounts,id'],
            'output_account_id' => ['nullable', 'exists:accounts,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_inclusive'] = $request->boolean('is_inclusive');
        $data['is_active'] = $request->boolean('is_active', true);

        $before = $taxCode->only(array_keys($data));
        $taxCode->update($data);
        $taxCode->refresh();
        $after = $taxCode->only(array_keys($data));

        $this->settings->forgetTax();

        $this->audit->log(
            description: __('Tax code updated: :code', ['code' => $taxCode->code]),
            event: 'settings.tax_code.updated',
            subject: $taxCode,
            properties: [
                'group' => 'tax_configuration',
                'before' => $before,
                'after' => $after,
            ],
            logName: AuditService::LOG_CONFIGURATION,
        );

        return redirect()->route('lfs-administration.settings.tax')->with('success', __('Tax code saved.'));
    }

    public function taxRateStore(Request $request, TaxCode $taxCode): RedirectResponse
    {
        $this->authorize('lfs-administration.manage');

        $data = $request->validate([
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $rate = $taxCode->rates()->create($data);
        $this->settings->forgetTax();

        $this->audit->log(
            description: __('Tax rate added for :code', ['code' => $taxCode->code]),
            event: 'settings.tax_rate.created',
            subject: $rate,
            properties: [
                'group' => 'tax_configuration',
                'tax_code_id' => $taxCode->id,
                'after' => $rate->only(['rate', 'effective_from', 'effective_to']),
            ],
            logName: AuditService::LOG_CONFIGURATION,
        );

        return redirect()->route('lfs-administration.settings.tax')->with('success', __('Tax rate added.'));
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    protected function logConfigurationChange(string $event, string $description, array $before, array $after): void
    {
        $this->audit->log(
            description: $description,
            event: $event,
            subject: null,
            properties: [
                'group' => 'system_settings',
                'before' => $before,
                'after' => $after,
            ],
            logName: AuditService::LOG_CONFIGURATION,
        );
    }
}
