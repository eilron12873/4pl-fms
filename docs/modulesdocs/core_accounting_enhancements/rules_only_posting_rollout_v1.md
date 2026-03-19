# Rules-Only Posting Rollout v1

Implemented controls:

- `config/core_accounting.php`
  - `rules_only_mode`
  - `fallback_telemetry_enabled`
- `GLPostingEngine` behavior:
  - throws `PostingRuleNotFoundException` when rules-only mode is enabled and no rule is found
  - logs fallback telemetry when rules-only mode is disabled and fallback path is used
- `FinancialEventController` deterministic error payloads:
  - `RULE_NOT_FOUND`
  - `PERIOD_LOCKED`
  - `JOURNAL_NOT_BALANCED`
  - `RULE_VALIDATION_FAILED`
  - `INTERNAL_ERROR`

## Rollout sequence

1. Enable telemetry in all non-prod environments.
2. Observe fallback logs and create missing rules.
3. Pilot `rules_only_mode=true` for selected event types/environment.
4. Promote to full production after no unresolved fallback events remain.

