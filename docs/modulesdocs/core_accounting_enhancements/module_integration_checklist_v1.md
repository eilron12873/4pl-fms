# Core Accounting Module Integration Checklist v1

Systems in scope:

- AR
- AP
- Billing Engine
- Procurement
- Treasury
- External WMS/LMS integrations

---

## 1. Contract Compliance

- [ ] Uses kebab-case `event_type`
- [ ] Sends required envelope fields:
  - [ ] `idempotency_key`
  - [ ] `source_system`
  - [ ] `source_reference`
  - [ ] `payload`
- [ ] Includes required payload fields for targeted event
- [ ] Uses canonical dimension keys

---

## 2. COA Compliance

- [ ] All referenced account codes are 6-digit `XYYZZZ`
- [ ] Posting accounts are leaf/detail (`is_posting = true`)
- [ ] No use of deprecated 4-digit account codes
- [ ] Legacy mappings documented where applicable

---

## 3. Posting Rule Compatibility

- [ ] Event type has active posting rule(s)
- [ ] Rule amount sources exist in payload
- [ ] Dimension mappings resolve correctly from payload
- [ ] Account resolvers (if configured) have valid target accounts
- [ ] Conditional rules are tested for expected branches

---

## 4. Idempotency and Error Handling

- [ ] Retry behavior preserves idempotency key
- [ ] Handles `duplicate` response safely
- [ ] Handles deterministic error codes (`RULE_NOT_FOUND`, `PERIOD_LOCKED`, etc.)
- [ ] Captures request/response correlation for reconciliation

---

## 5. Testing and Sign-Off

- [ ] Event happy-path test passed
- [ ] Duplicate replay test passed
- [ ] Invalid payload test passed
- [ ] Closed period test passed
- [ ] Finance sign-off completed for posting outputs
- [ ] Integration owner sign-off completed

