# Production Rollout and Rollback Runbook v1

## 1. Rollout Stages

1. staging full-volume simulation
2. pilot subset of entities/events
3. phased production enablement by event family
4. full enablement + hypercare monitoring

## 2. Pre-Go-Live Checklist

- [ ] COA governance frozen
- [ ] accounting contract v1 published
- [ ] posting rule versions approved/active
- [ ] rules-only mode validated in pilot scope
- [ ] period close controls validated
- [ ] release gate tests passing
- [ ] pilot reconciliation signed off

## 3. Deployment Steps

1. deploy application and migrations
2. apply approved posting-rule activations
3. enable feature flags as planned:
   - `CORE_ACCOUNTING_FALLBACK_TELEMETRY=true`
   - `CORE_ACCOUNTING_RULES_ONLY_MODE` per rollout scope
4. monitor posting errors, duplicates, latency, and reconciliation deltas

## 4. Stop/Abort Criteria

Trigger immediate hold if any occurs:

- unresolved `RULE_NOT_FOUND` on in-scope events
- reconciliation variance above approved threshold
- sustained posting error spike
- period lock violations or unauthorized reopen attempts

## 5. Rollback Procedure

1. disable rules-only mode for affected scope.
2. revert to previous approved posting-rule version(s).
3. pause impacted integrations if needed.
4. reconcile partial postings and isolate failed batch.
5. communicate incident status to finance and operations.

## 6. Post-Rollback Evidence

- incident timeline
- impact scope
- corrective action plan
- retest and re-approval checkpoint

