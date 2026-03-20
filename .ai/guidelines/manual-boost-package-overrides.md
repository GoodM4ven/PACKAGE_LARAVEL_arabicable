# Custom Instructions

## Workbench Commands

- Prefer `php workbench/artisan ...` for Laravel / Boost commands in this package repository.

## Testing Commands

- Prefer Composer scripts for Pest runs:
  - `composer test`
  - `composer testel`
  - `composer testcov`
- These scripts run through `.scripts/run-pest.sh`, which temporarily disables Pest Browser plugin loading by patching `vendor/pest-plugins.json` and sets `PEST_ENABLE_BROWSER_PLUGIN=0`.
- Browser-only tests are grouped under `browser` and are excluded in normal AI/local runs.
