# Treasury & Cash Module – Technical & Functional Specification

## 1. Purpose & Scope

The **Treasury & Cash** module manages **bank accounts, cash movements, and bank reconciliation** for LFS.

Its primary goals are to:

- Maintain a **bank account master** with balances synced to GL movements.
- Record **bank transactions** (deposits, withdrawals, transfers, fees, adjustments).
- Provide a **consolidated cash position** by account and currency.
- Support **bank reconciliation** by matching bank statements to recorded transactions.

---

## 2. Tech Stack & Module Architecture

- **Framework**: Laravel 12 (PHP 8.4)
- **Module location**: `app/Modules/Treasury`
- **Layers**:
  - `Domain`: `Treasury` domain root and treasury concepts.
  - `Application`: `TreasuryService`, `TreasuryOverview`.
  - `Infrastructure`: Eloquent models (`BankAccount`, `BankTransaction`, `BankStatementLine`), `TreasuryRepository`, base `TreasuryModel`.
  - `UI`: `TreasuryController` and Blade views (dashboard, bank accounts, transactions, reconciliation).
  - `API`: `api.php` placeholder for bank-related APIs.
- **Service provider**: `TreasuryServiceProvider` registers routes, views, and services.

Database tables (via migrations):

- `bank_accounts` – bank account master data.
- `bank_transactions` – ledger of internal bank transactions.
- `bank_statement_lines` – imported or manually entered bank statement lines for reconciliation.

Integration:

- Used by:
  - **Accounts Payable** (AP payments, check printing via bank accounts; see `Accounts_Payable_Module_Documentation.md`).
  - **Financial Reporting** (cash flow, cash position reports; see `Financial_Reporting_Module_Documentation.md`).

---

## 3. Key Components

### 3.1 Models (Infrastructure)

- `BankAccount`
  - Fields:
    - `name`, `bank_name`, `account_number`, `currency`.
    - `gl_account_code` (link to GL cash account).
    - `opening_balance`, `opened_at`, `is_active`, `notes`.
  - Casts: `opening_balance` (decimal), `opened_at` (date), `is_active` (boolean).
  - Relations:
    - `transactions()` → `BankTransaction`.
    - `statementLines()` → `BankStatementLine`.
  - Accessors:
    - `balance` → `opening_balance + sum(transactions.amount)` (using eager-loaded sum when available).

- `BankTransaction`
  - Represents a cash movement recorded in LFS:
    - Fields: `bank_account_id`, `transaction_date`, `description`, `amount`, `reference`, `type`, `source_type`, `source_id`, `reconciled_at`.
    - Types include: `deposit`, `withdrawal`, `transfer`, `fee`, `adjustment`.
  - Casts: `transaction_date` (date), `amount` (decimal), `reconciled_at` (datetime).
  - Relations:
    - `bankAccount()` → `BankAccount`.
    - `statementLine()` → `BankStatementLine` (one-to-one link).
  - Helper:
    - `isReconciled()` – true if `reconciled_at` is not null.

- `BankStatementLine`
  - Represents a **line from a bank statement**:
    - Fields: `bank_account_id`, `statement_date`, `description`, `amount`, `reference`, `bank_sequence`, `bank_transaction_id`, `matched_at`.
  - Casts: `statement_date` (date), `amount` (decimal), `matched_at` (datetime).
  - Relations:
    - `bankAccount()` → `BankAccount`.
    - `bankTransaction()` → `BankTransaction`.
  - Helper:
    - `isMatched()` – true if `bank_transaction_id` is set.

### 3.2 TreasuryService (Application Layer)

`TreasuryService` provides core treasury operations:

- **Create bank account**
  - `createAccount(array $data): BankAccount`
    - Ensures `opened_at` defaults to today if not provided.
    - Creates `BankAccount` with given GL code and opening balance.

- **Record transaction**
  - `recordTransaction(int $bankAccountId, string $transactionDate, string $description, float $amount, string $type = 'deposit', ?string $reference = null, ?string $sourceType = null, ?int $sourceId = null): BankTransaction`
    - Creates a `BankTransaction` with optional linkage to a **source document** (`source_type`, `source_id`).
    - Amount sign convention is flexible:
      - UI hints positive = deposit, negative = withdrawal.

- **Cash position**
  - `cashPosition(): array{accounts: Collection, total_by_currency: array<string,float>}`
    - Retrieves all active bank accounts with summed transactions.
    - For each account, computes `balance = opening_balance + transactions_sum_amount`.
    - Aggregates balances into `total_by_currency`.
    - Used by the dashboard to present per-account and per-currency cash.

- **Account balance**
  - `getAccountBalance(BankAccount $account): float`
    - Helper to compute balance from a single account’s transactions.

- **Bank reconciliation**
  - `matchStatementLineToTransaction(BankStatementLine $statementLine, BankTransaction $transaction)`
    - Validates:
      - Both belong to the same bank account.
      - Statement line is not already matched.
      - Transaction is not already reconciled.
    - Marks line as matched (`bank_transaction_id`, `matched_at`) and transaction as reconciled (`reconciled_at`).
  - `unmatchStatementLine(BankStatementLine $statementLine)`
    - Clears `bank_transaction_id` and `matched_at` on the statement line.
    - Clears `reconciled_at` on the linked transaction (if any).
  - `addStatementLine(int $bankAccountId, string $statementDate, float $amount, ?string $description, ?string $reference, ?string $bankSequence): BankStatementLine`
    - Creates a new `BankStatementLine` for reconciliation.

### 3.3 Controller & Routes (UI Layer)

`TreasuryController` wires UI to `TreasuryService`:

- Dashboard:
  - `index()` – shows **Treasury & Cash** home with cash position.
- Bank accounts:
  - `bankAccounts()` – list all bank accounts.
  - `bankAccountCreate()` / `bankAccountStore()` – create new accounts.
  - `bankAccountShow()` – show one account and its transactions.
- Bank transactions:
  - `transactionCreate()` / `transactionStore()` – record manual bank transactions.
- Reconciliation:
  - `reconciliation()` – reconciliation workspace for one bank account.
  - `matchReconciliation()` – match a bank statement line to a bank transaction.
  - `unmatchReconciliation()` – undo a match.
  - `statementLineStore()` – add a new statement line.

Routes (`app/Modules/Treasury/routes.php`):

- Prefix: `treasury`
- Name: `treasury.*`
- Middleware: `auth`, `verified`, `permission:treasury.view`  
  (mutating actions use `treasury.manage`).

Key routes:

- Dashboard:
  - `GET /treasury` → `index()`.
- Bank accounts:
  - `GET /treasury/bank-accounts` → `bankAccounts()`.
  - `GET /treasury/bank-accounts/create` → `bankAccountCreate()`.
  - `POST /treasury/bank-accounts` → `bankAccountStore()`.
  - `GET /treasury/bank-accounts/{id}` → `bankAccountShow()`.
- Transactions:
  - `GET /treasury/bank-accounts/{accountId}/transactions/create` → `transactionCreate()`.
  - `POST /treasury/transactions` → `transactionStore()`.
- Reconciliation:
  - `GET /treasury/reconciliation` → `reconciliation()`.
  - `POST /treasury/reconciliation/match` → `matchReconciliation()`.
  - `POST /treasury/reconciliation/unmatch/{statementLineId}` → `unmatchReconciliation()`.
  - `POST /treasury/reconciliation/statement-lines` → `statementLineStore()`.

---

## 4. Navigation Menus & Screens

### 4.1 Treasury & Cash Dashboard

Path: `Treasury & Cash → Home` (`/treasury`).

Cards:

- **Bank Accounts**
  - Route: `/treasury/bank-accounts`.
  - Manage and view all bank accounts and balances.
- **Bank Reconciliation**
  - Route: `/treasury/reconciliation`.
  - Match statement lines to internal transactions.

Cash position section:

- Table shows:
  - Account name.
  - Bank / Account number.
  - Balance (computed from opening balance + transactions).
  - View link to bank account detail.
- Totals by currency:
  - e.g. “Total (USD): …”, “Total (EUR): …”.

### 4.2 Bank Accounts

- List page:
  - Route: `GET /treasury/bank-accounts`.
  - Table columns:
    - Name.
    - Bank / Account #.
    - Currency.
    - Balance (from `BankAccount::balance`).
    - Status (Active/Inactive).
    - Actions (`View`).
  - Actions:
    - **Add account** (if `treasury.manage`) → `/treasury/bank-accounts/create`.

- Add account:
  - Route: `GET /treasury/bank-accounts/create`.
  - Fields:
    - Name.
    - Bank name, account number.
    - Currency.
    - Optional GL account code (defaults to `1400`).
    - Opening balance and opened-at date.
    - Notes.
  - On submit:
    - `POST /treasury/bank-accounts` → `bankAccountStore()` → `TreasuryService::createAccount()`.

- Account detail:
  - Route: `GET /treasury/bank-accounts/{id}`.
  - Shows:
    - Bank, account number, currency, current balance.
  - Transactions table:
    - Date, description, type, amount (positive/negative color-coded), reconciled flag.
    - Paginated.
  - Actions:
    - **Record transaction** (if `treasury.manage`) → `/treasury/bank-accounts/{id}/transactions/create`.

### 4.3 Record Transaction

- Route: `GET /treasury/bank-accounts/{accountId}/transactions/create`.
- Bound to a specific bank account.
- Fields:
  - Date.
  - Type (Deposit, Withdrawal, Transfer, Fee, Adjustment).
  - Description.
  - Amount (with hint: positive for deposit, negative for withdrawal).
  - Reference.
- On submit:
  - `POST /treasury/transactions` → `transactionStore()` → `TreasuryService::recordTransaction()`.
  - Redirects back to the bank account detail with updated transactions and balance.

### 4.4 Bank Reconciliation

- Route: `GET /treasury/reconciliation`.
- Step 1 – Select account:
  - Bank account dropdown (required).
  - `View` loads reconciliation workspace for that account.

- Step 2 – View reconciliation workspace:
  - Header shows:
    - Account name and **book balance** (`BankAccount::balance`).
  - Add statement line (if `treasury.manage`):
    - Date, amount, description, reference.
    - Posts to `reconciliation.statement-lines.store`.
  - Unmatched statement lines:
    - Shows date, description, amount.
    - For each line:
      - Dropdown of unreconciled transactions for that account.
      - Form to **Match** line to a transaction (posts to `reconciliation.match`).
  - Unreconciled transactions:
    - Lists date, description, amount for transactions without `reconciled_at`.

- Unmatch:
  - Via `POST /treasury/reconciliation/unmatch/{statementLineId}`:
    - Clears match and reconciliation status, returning both items to the unmatched lists.

Use cases:

- Regular **bank reconciliation** to ensure GL and bank statement agree.
- Identifying unexplained entries on the statement or in the GL.

---

## 5. End-to-End Workflows

### 5.1 Daily Cash Management

1. Treasury opens the **Treasury & Cash dashboard** to view:
   - Per-account balances.
   - Totals by currency.
2. If necessary, they:
   - Add new bank accounts.
   - Record bank transactions not already mirrored by AP/AR (e.g. fees, manual transfers).

### 5.2 Bank Reconciliation Workflow

1. Download a bank statement from the bank.
2. For each statement line:
   - Create a `BankStatementLine` via:
     - Manual entry (Add statement line), or
     - Future automated import.
3. Match statement lines to existing GL-based bank transactions:
   - For each unmatched line, choose an unreconciled internal transaction and click **Match**.
   - If no transaction exists yet, optionally create it first via **Record transaction**.
4. After reconciliation:
   - Unmatched statement lines highlight missing or misposted items.
   - Unreconciled transactions highlight GL entries without a bank statement counterpart.

---

## 6. Design Decisions & Guarantees

- **Separation of Roles**
  - Treasury focuses on **cash movements and reconciliation**, while GL postings (AP/AR, etc.) are handled in their respective modules using Core Accounting (see `Core_Accounting_Module_Documentation.md`).

- **Flexible Amount Sign Handling**
  - Amount sign is under user control (positive/negative), which is useful for:
    - Multi-currency and bank-specific conventions.
    - Representing fees and adjustments.

- **Explicit Reconciliation State**
  - `BankTransaction::isReconciled()` and `BankStatementLine::isMatched()` provide clear flags for reconciliation status.
  - Enables reporting on reconciled vs. unreconciled lines.

---

## 7. Recommended Enhancements

These are **optional improvements** for a richer Treasury & Cash module.

### 7.1 GL Integration for Bank Transactions

- Optionally integrate `recordTransaction()` with Core Accounting (see `Core_Accounting_Module_Documentation.md`):
  - Auto-post GL journals for non-AP/AR cash movements (e.g. bank fees, manual transfers).
  - Ensure cash accounts in GL always reconcile with Treasury balances.

### 7.2 Bank Statement Import

- Add:
  - CSV or MT940/ISO20022 import functionality to create `BankStatementLine` records in bulk.
  - Matching heuristics (by amount/date/reference) to auto-suggest or auto-match transactions.

### 7.3 Multi-Currency Enhancements

- Introduce:
  - Functional currency and FX revaluation support for cash positions.
  - Normalized totals in a base currency.

### 7.4 Cash Forecasting

- Extend `TreasuryService` to:
  - Forecast future cash positions from:
    - AP payment schedules.
    - AR expected receipts.
  - Provide dashboards for short-term liquidity planning.

### 7.5 Access Control & Audit

- Add:
  - More granular permissions (e.g. “view balances only” vs. “record transactions” vs. “reconcile”).
  - Audit logs of bank account changes, transaction edits, and reconciliation actions.

### 7.6 Dashboards & KPIs

- Provide:
  - Cash concentration by bank/region.
  - Bank fee analysis (totals, trends).
  - Reports on reconciliation lag and outstanding unreconciled items.

---

## 8. Summary

The Treasury & Cash module provides a focused, operational layer for:

- Managing bank accounts and cash balances.
- Recording bank-side movements not covered elsewhere.
- Performing structured bank reconciliation.

The recommended enhancements aim to deepen integration with GL, automate bank statement handling, and provide better visibility and planning capabilities for treasury operations.

