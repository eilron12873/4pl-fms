# Release Gate Test and Benchmark v1

## Automated tests added

- `tests/Feature/CoreAccounting/FinancialEventContractTest.php`
  - validates kebab-case event-type contract enforcement
- `tests/Unit/CoreAccounting/PostingRuleLifecycleServiceTest.php`
  - validates lifecycle transition rules

## Benchmark tooling added

- Artisan command: `core-accounting:benchmark-events`

Example:

```bash
php artisan core-accounting:benchmark-events shipment-delivered --count=10000 --duplicate-rate=10
```

Dry run:

```bash
php artisan core-accounting:benchmark-events shipment-delivered --count=10000 --dry-run
```

## Release gate baseline

- tests must pass in CI
- benchmark report attached for candidate release
- investigate if error rate > 0% or throughput below agreed threshold

