# Period Close Controls v1

Implemented controls:

- pre-close gate checks via `PeriodCloseGateService`
  - unbalanced journals check
  - integration error check
- close evidence storage in `period_close_evidences`
- controlled reopen workflow with mandatory reason prompt
- reopen actions logged in:
  - `period_change_logs` (`action = reopened`)
  - `period_close_evidences` metadata
  - audit log event (`period.reopened`)

## Governance behavior

- A period cannot be closed if pre-close checks fail.
- Every close attempt stores evidence output for finance/audit review.
- Reopen requires explicit reason for traceability.

