# CI

Contract checks work best when they run before a message change is merged. The
package repository already tests multiple PHP and Laravel versions. Applications
using the package can add contract-specific checks around examples, schemas, and
compatibility snapshots.

## Existing Package Workflows

This repository includes two GitHub Actions workflows:

| Workflow | Purpose |
| --- | --- |
| `.github/workflows/ci.yml` | Runs Pint, Rector dry-run, PHPStan, and Pest across PHP 8.2, 8.3, 8.4 and Laravel 10, 11, 12, 13 where supported. |
| `.github/workflows/run-tests.yml` | Runs tests on prefer-lowest and prefer-stable dependencies, plus style and PHPStan checks. |

Run the same full local quality gate with:

```bash
composer ci
```

## Application Contract Check

In an application that uses this package, add a job that validates registered
contract examples and exports schemas:

```yaml
name: Message Contracts

on:
  pull_request:
    branches: [main]

jobs:
  contracts:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3" # Update this to match your project's PHP version
          extensions: mbstring, pdo, sqlite, pdo_sqlite
          coverage: none

      - run: composer install --prefer-dist --no-interaction --no-progress

      - run: php artisan message-contracts:validate-examples --fail-on-missing-example

      - run: php artisan message-contracts:export-json-schema --output=docs/schemas --fail-on-warning
```

Commit generated schemas only if your team wants schema files reviewed in pull
requests. Otherwise, export them as build artifacts.

> **Note:** While the example above uses GitHub Actions, the same Artisan commands 
> (`message-contracts:validate-examples`, `message-contracts:export-json-schema`, 
> etc.) work identically in GitLab CI, Bitbucket Pipelines, CircleCI, or any 
> other CI/CD platform.

## Compatibility Gate

If consumers rely on existing payloads, keep a snapshot on the default branch
and compare pull requests against it.

```yaml
- name: Check message contract compatibility
  run: |
    php artisan message-contracts:check-breaking-changes \
      --against=message-contracts.snapshot.json \
      --fail-on-warning
```

Create or refresh the snapshot intentionally:

```bash
php artisan message-contracts:snapshot --output=message-contracts.snapshot.json
```

Do not update the snapshot in the same change that silently breaks consumers.
For breaking payload changes, add a new contract version and keep the old one
registered while consumers migrate.

## Suggested CI Order

Run contract checks before the full test suite when you want fast feedback:

```bash
php artisan message-contracts:validate-examples --fail-on-missing-example
php artisan message-contracts:export-json-schema --output=docs/schemas --fail-on-warning
php artisan message-contracts:check-breaking-changes --against=message-contracts.snapshot.json
composer ci
```

Skip the compatibility command until your application has created an initial
snapshot.



---

**Previous:** [Checks](checks.md) | **Next:** [Comparison](comparison.md)
