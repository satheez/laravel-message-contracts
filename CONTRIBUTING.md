# Contributing

Thank you for your interest in contributing to Laravel Message Contracts.

## Local Setup

```bash
git clone https://github.com/satheez/laravel-message-contracts.git
cd laravel-message-contracts
composer install
```

## Running Tests

```bash
vendor/bin/pest
```

## Code Style

```bash
vendor/bin/pint
```

To check without fixing:

```bash
vendor/bin/pint --test
```

## Static Analysis

```bash
vendor/bin/phpstan analyse
```

## Automated Refactoring (Rector)

Check for suggested changes (dry-run):

```bash
vendor/bin/rector process --dry-run
```

Apply changes:

```bash
vendor/bin/rector process
```

Rector enforces PHP 8.2 modernization, dead code removal, type declaration completeness, and early-return patterns. Run it before opening a PR and commit any changes it produces.

## Branch Naming

- `feature/<name>` — new features
- `fix/<name>` — bug fixes
- `chore/<name>` — tooling, dependency updates, documentation

## Pull Request Guidelines

- Keep PRs focused on a single concern.
- All new code must be covered by tests.
- PHPStan must pass at level 8.
- Pint formatting must be clean.
- Rector dry-run must produce no changes.
- Add an entry to `CHANGELOG.md` under `[Unreleased]`.

## Testing Expectations

- Unit & Feature tests are written using **Pest PHP**.
- Integration/Console tests use **Orchestra Testbench** (extending `Satheez\MessageContracts\Tests\TestCase`).
- Use test fixture message contracts under `tests/Fixtures/MessageContracts/` for stubbing payload data structures.
- Clean up any generated files (e.g., temporary JSON Schemas, snapshots) using Laravel's `File` facade within the test block.
