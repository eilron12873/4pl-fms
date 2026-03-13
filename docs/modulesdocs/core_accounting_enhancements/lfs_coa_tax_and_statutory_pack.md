## LFS COA Tax & Statutory Pack

System: `LFS` — Logistics Financial System  
Version: `1.0`  
Scope: Standard PH-focused example (VAT, withholding, SSS/HDMF/PhilHealth, income tax), designed so it can be adapted per country.

---

## 1. Purpose and Design Goals

This document defines how to **extend the LFS Chart of Accounts (COA)** with a standard **“tax + statutory pack”** and how to **map client QuickBooks (QB) tax/statutory accounts** into these LFS accounts and dimensions.

Goals:

- Provide a **product-standard** set of tax/statutory accounts that:
  - Work across multiple logistics clients in the same country.
  - Are easy to adapt for other countries and regimes.
- Avoid importing each client’s ad‑hoc tax accounts into LFS.
- Support:
  - VAT (input, output, control/suspense).
  - Withholding taxes (compensation, expanded/final).
  - Statutory payroll contributions (SSS, HDMF, PhilHealth or equivalents).
  - Income tax, MCIT, deferred tax.
  - Common regulatory provisions and penalties.

This is an **extension** on top of the base LFS COA described in `lfs_chart_of_accounts_master_design.md` and `lfs_coa_structure_and_numbering.md`.

---

## 2. Core Concepts

### 2.1 Separate “type” vs “tax role”

In LFS, base account types remain simple:

- `Asset`, `Liability`, `Equity`, `Revenue`, `Expense`.

The **tax nature** of an account is defined by:

- **Code range** (e.g. `207x` for VAT/withholding, `13xx` for prepaid tax).
- **Additional classification fields**, such as:
  - `tax_category` (e.g. `vat_input`, `vat_output`, `withholding_comp`, `withholding_expanded`, `income_tax_current`, `income_tax_deferred`).
  - `payroll_statutory_type` (e.g. `sss`, `hdmf`, `philhealth`).
  - `region` or `tax_jurisdiction` (e.g. `Luzon`, `VISMIN`).

### 2.2 Dimensions for region and jurisdiction

Region‑specific QB accounts (e.g. “Output VAT (VISMIN)”) should **not** become separate GL accounts in LFS. Instead:

- Keep **one LFS VAT Output payable account**.
- Use **dimensions** to identify region or entity:
  - `tax_region` or `jurisdiction` (if modeled as a dimension).
  - Or `cost_center` / `branch_id` for region/branch‑specific tax.

---

## 3. Proposed LFS Tax & Statutory Account Ranges

The pack stays within the existing major groups:

- **Assets (`1000` range)**: Tax assets (prepaid taxes, input VAT, deferred tax assets).
- **Liabilities (`2000` range)**: Tax liabilities (output VAT, withholding, income tax payable, deferred tax liabilities).
- **Expenses (`6000` range)**: Tax expenses (income tax, penalties) and statutory payroll expenses.
- **Other expenses (`8000` range)**: Penalties and fines if you prefer to separate.

> Note: Codes are examples. You can adjust specific numbers as long as you keep the **grouping logic** consistent.

### 3.1 Tax Assets (Current Assets)

Suggested accounts under `13xx`:

- `1310` — Prepaid Income Tax  
- `1320` — Input VAT — Goods  
- `1321` — Input VAT — Services  
- `1322` — Input VAT — Importation (optional if needed)  
- `1330` — Excess Input VAT Carry Over  
- `1340` — Other Tax Credits / Prepayments

Usage:

- **Prepaid income tax**: advance tax payments not yet applied.
- **Input VAT**: recoverable VAT on purchases; detailed by nature via subaccounts.
- **Excess input VAT**: carried forward for future offsetting.

### 3.2 Tax Liabilities (Current Liabilities)

Extend the `207x` / `208x` range for taxes payable.

Examples:

- `2070` — Taxes Payable (generic, existing)  
- `2071` — VAT Output Payable – Standard Rate  
- `2072` — VAT Payable – Other / Special Rates (if applicable)  
- `2073` — VAT Control / Suspense  
- `2074` — Withholding Tax – Compensation  
- `2075` — Withholding Tax – Expanded  
- `2076` — Withholding Tax – Final  
- `2077` — Income Tax Payable – Current  
- `2078` — MCIT Payable (if applicable)  
- `2079` — Other Tax Payables

These are **current liabilities**; position and maturity are driven by local law.

### 3.3 Deferred Tax (Non‑current)

You can use a small `23xx` or `24xx` range for deferred tax.

Examples:

- `2310` — Deferred Tax Asset  
- `2320` — Deferred Tax Liability

Classification:

- Type: `Asset` (for `2310`) and `Liability` (for `2320`).
- `tax_category`: `income_tax_deferred`.

### 3.4 Statutory Payroll Contributions (Liabilities)

Extend `206x` or `208x` for statutory payroll contributions:

- `2061` — SSS Contributions Payable  
- `2062` — HDMF Contributions Payable  
- `2063` — PhilHealth Contributions Payable  
- `2064` — Other Statutory Payroll Payable

These typically arise from:

- Gross payroll entry: recognize employer portion in expenses and liability.
- Remittance: clear the liability when paid.

### 3.5 Tax & Statutory Expenses

Within `6000` range, you can either:

- Keep a **single group** for tax expenses and statutory contributions, or
- Use a **sub‑range** like `6700` for tax and regulatory expenses.

Examples:

- `6700` — Tax & License Expenses  
- `6710` — Income Tax Expense – Current  
- `6720` — Income Tax Expense – Deferred  
- `6730` — Other Tax & Regulatory Expenses  
- `6740` — Statutory Contributions – Employer (SSS/HDMF/PhilHealth, if you want to group them here rather than payroll)

Penalties and fines can either:

- Stay in `8020` (Other Expenses – Penalties and Fines) per your master design, or  
- Be mirrored as a `67xx` subaccount if you want to track them nearer tax expenses.

---

## 4. Mapping QuickBooks Tax / Statutory Accounts to LFS

This section gives **patterns** for mapping, using the example QB COA you imported (`Chart of Accounts QB.csv`).

### 4.1 VAT‑related Accounts

Common QB examples:

- Input VAT:  
  - `109002` Input VAT – Local Purchases  
  - `109003` Input VAT – Services  
  - Region‑specific variants (`VISMIN`) etc.
- Output VAT and control/suspense:  
  - `203002` Output VAT  
  - `203006` Output VAT (VISMIN)  
  - various “VAT Control / VAT Suspense / Output VAT Suspense” accounts.

**LFS target accounts and dimensions:**

- **Input VAT**:
  - Map to `1320` Input VAT – Goods or `1321` Input VAT – Services.
  - If region‑specific (`VISMIN`), use:
    - Same LFS account, plus a **dimension** like `tax_region = VISMIN` or `cost_center = VISMIN`.
- **Excess Input VAT**:
  - Map QB’s “Excess Input VAT Carry Over” to `1330` Excess Input VAT Carry Over.
- **Output VAT**:
  - Map QB “Output VAT” and variants to:
    - `2071` VAT Output Payable – Standard Rate.
    - When you have multiple official VAT rates, use `2072` or additional subaccounts.
  - Region‑specific: again, use a `tax_region` / `cost_center` dimension, not new GL codes.
- **VAT Control / Suspense**:
  - Map all QB “VAT Control / Suspense” type accounts to:
    - `2073` VAT Control / Suspense.

### 4.2 Withholding Taxes

QB examples:

- `203003` Withholding Tax – Compensation  
- `203004` Withholding Tax – Expanded  
- `203005` Withholding Tax – Final  
- Region‑specific variants.

LFS targets:

- `2074` — Withholding Tax – Compensation  
- `2075` — Withholding Tax – Expanded  
- `2076` — Withholding Tax – Final

Region‑specific use the same accounts, with `tax_region` or branch dimensions.

### 4.3 Income Tax and MCIT

QB examples:

- `203001` Income Tax Payable  
- `207002` MCIT Payable  
- `614001` Provision for Income Tax – Current  
- `614002` Provision for Income Tax – Deferred  
- `614003` Provision for Income Tax – Final.

LFS mapping:

- **Liabilities**:
  - `203001` Income Tax Payable → `2077` Income Tax Payable – Current.
  - `207002` MCIT Payable → `2078` MCIT Payable.
- **Expenses**:
  - `614001` Provision for Income Tax – Current → `6710` Income Tax Expense – Current.
  - `614002` Provision for Income Tax – Deferred → `6720` Income Tax Expense – Deferred.
  - `614003` Provision for Income Tax – Final → `6710` or a dedicated `6715` if you want to separate final tax.

Closing entries will move balances between:

- Income tax expense (`671x`) and income tax payable (`207x`).

### 4.4 Statutory Payroll Contributions (SSS / HDMF / PhilHealth)

QB examples:

- `202001` HDMF Premium Payable  
- `202002` HDMF Loans Payable  
- `202003` HDMF Housing Loans Payable  
- `202004` SSS Premium Payable  
- `202006` PhilHealth Premium Payable  
- Various payroll expense lines in `6010xx` / `8100xx`.

LFS targets:

- Liabilities:
  - `2061` SSS Contributions Payable  
  - `2062` HDMF Contributions Payable  
  - `2063` PhilHealth Contributions Payable.
- Expenses:
  - Employer portion of contributions can either:
    - Stay under payroll expenses (e.g. `6100` Salaries and Wages, with payroll breakdowns in subaccounts), or
    - Use dedicated `6740` Statutory Contributions – Employer if you want to track them separately.

Mapping logic:

- QB “Premium Payable” accounts → LFS `206x` liabilities.  
- QB payroll expense lines (SSS/HDMF/PhilHealth) → LFS payroll expense accounts; break out to stat contributions if desired.

### 4.5 Deferred Tax

QB example:

- `207001` Deferred Income Tax Liability.

LFS targets:

- `2320` Deferred Tax Liability (liability side).  
- Corresponding expense entries go to `6720` Income Tax Expense – Deferred.

---

## 5. Mapping Workflow for Clients

### 5.1 Step-by-step approach

1. **Inventory QB accounts**
   - Export the client’s QB COA (as CSV or Excel).
   - Identify accounts with `Account type` or `Detail type` related to:
     - VAT/input/output, withholding, income tax, MCIT.
     - SSS/HDMF/PhilHealth or equivalent statutory contributions.
     - Tax suspense / control accounts.

2. **Classify by tax role**
   - For each QB account, classify:
     - `tax_role`: `input_vat`, `output_vat`, `vat_suspense`, `withholding_comp`, `withholding_expanded`, `withholding_final`, `income_tax_payable`, `income_tax_expense`, `statutory_contribution`, etc.
     - `region` / `jurisdiction` if the name includes VISMIN, City, etc.

3. **Assign LFS tax account**
   - Using the ranges in section 3:
     - Choose the **single best LFS account** for each tax role.
     - Avoid duplicating accounts for regions, branches, clients—use dimensions instead.

4. **Attach dimensions**
   - For region-specific QB accounts, define:
     - `tax_region` or `branch_id` dimension values.
   - For entity/group structures, dimension `entity_id` where you have multiple legal entities.

5. **Document mapping**
   - Maintain a mapping file (CSV/Excel) with:
     - `qb_number`, `qb_name`, `qb_account_type`, `qb_detail_type`
     - `lfs_account_code`, `lfs_account_name`
     - `tax_category`, `tax_region`, `notes`

6. **Configure posting rules**
   - Update LFS posting rules so that:
     - Purchase invoices with VAT use `132x` input VAT accounts.
     - Sales invoices with VAT use `207x` output VAT accounts.
     - Payroll runs generate the correct `206x` contributions payable.
     - Tax returns and payment processes clear `207x` liabilities and `13xx` assets appropriately.

7. **Test with sample periods**
   - Take a closed month from QB.
   - Rebuild the same month in LFS:
     - Post representative purchase, sales, and payroll transactions.
     - Run trial balance and key tax reports.
     - Ensure that VAT, withholding, income tax, and statutory balances reconcile.

---

## 6. Example: Mapping Selected QB Accounts to LFS Tax Pack

Illustrative mappings (based on the example QB COA):

- `109002` Input VAT – Local Purchases → `1320` Input VAT – Goods (`tax_category = vat_input`, `tax_region = HQ`)  
- `109003` Input VAT – Services → `1321` Input VAT – Services (`tax_category = vat_input`)  
- `109010` Excess Input VAT Carry Over → `1330` Excess Input VAT Carry Over  
- `203002` Output VAT → `2071` VAT Output Payable – Standard Rate (`tax_category = vat_output`)  
- `203006` Output VAT (VISMIN) → `2071` VAT Output Payable – Standard Rate (`tax_region = VISMIN`)  
- `203003` Withholding Tax – Compensation → `2074` Withholding Tax – Compensation  
- `203004` Withholding Tax – Expanded → `2075` Withholding Tax – Expanded  
- `203005` Withholding Tax – Final → `2076` Withholding Tax – Final  
- `203001` Income Tax Payable → `2077` Income Tax Payable – Current  
- `207002` MCIT Payable → `2078` MCIT Payable  
- `202004` SSS Premium Payable → `2061` SSS Contributions Payable  
- `202001` HDMF Premium Payable → `2062` HDMF Contributions Payable  
- `202006` PhilHealth Premium Payable → `2063` PhilHealth Contributions Payable  
- `614001` Provision for Income Tax – Current → `6710` Income Tax Expense – Current  
- `614002` Provision for Income Tax – Deferred → `6720` Income Tax Expense – Deferred  
- `615006` Penalties, Surcharge, Others → `8020` Penalties and Fines

These examples show how multiple QB accounts often map to a **smaller, well-structured set** of LFS tax/statutory accounts, with **dimensions** capturing region or other segmentation instead of multiplying GL codes.

---

## 7. Adapting the Pack for Other Countries

While this example is informed by PH-like requirements, the pattern is generic:

- Keep **base types** simple: Asset, Liability, Equity, Revenue, Expense.
- Use **number ranges** within `13xx`, `20xx`, `23xx`, `60xx`, `80xx` for:
  - Tax assets, tax liabilities, deferred taxes, tax expenses, penalties.
- Add **classification fields** (`tax_category`, `jurisdiction`, `payroll_statutory_type`).
- Use **dimensions** for regions, entities, and branches instead of copying tax accounts per region.

When onboarding a new country:

- Introduce a **country‑specific tax profile** (VAT vs GST vs sales tax, etc.).
- Reuse the same structural approach:
  - Input tax assets (`13xx`), output/payable (`20xx`), deferred (`23xx`), expenses (`67xx`), penalties (`80xx`).

This makes the LFS COA:

- **Consistent** across clients and countries.  
- **Maintainable** in code and reporting.  
- **Expressive enough** for local tax/regulatory requirements without sacrificing the clean logistics‑first design.

