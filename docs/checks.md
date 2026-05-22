# Checks

This package includes command-line checks for registered contracts, payload
examples, schema exports, and compatibility snapshots. These commands are safe
to run locally and in CI.

## List Registered Contracts

```bash
php artisan message-contracts:list
```

Use JSON output when another tool needs to read the registered contracts:

```bash
php artisan message-contracts:list --format=json
```

The list output includes the contract name, version, class, deprecation state,
whether an `example()` exists, and the number of validation rules.

## Validate One Payload

Validate an inline payload against a registered contract:

```bash
php artisan message-contracts:validate user.registered \
  --contract-version=1 \
  --json='{"user_id":123,"email":"john@example.com","registered_at":"2026-05-22T04:45:00Z"}'
```

Validate a payload file:

```bash
php artisan message-contracts:validate user.registered \
  --contract-version=1 \
  --file=payload.json
```

Validate a full message envelope instead of a raw payload:

```bash
php artisan message-contracts:validate user.registered \
  --message \
  --file=message.json
```

When `--message` is present, the command reads the `contract` and `version`
from the envelope and validates the enclosed payload through the registry.

## Validate Contract Examples

Every contract can expose a valid sample payload through `example()`. Validate
all registered examples with:

```bash
php artisan message-contracts:validate-examples
```

Require every registered contract to provide an example:

```bash
php artisan message-contracts:validate-examples --fail-on-missing-example
```

This is useful when examples feed generated docs or shared payload fixtures.

## Export Schemas

Export payload schemas:

```bash
php artisan message-contracts:export-json-schema --output=docs/schemas --pretty
```

Export payload schemas and full message-envelope schemas:

```bash
php artisan message-contracts:export-json-schema \
  --output=docs/schemas \
  --include-message-envelope
```

Fail when the schema mapper finds unsupported Laravel validation rules:

```bash
php artisan message-contracts:export-json-schema --fail-on-warning
```

Unsupported rules are reported as warnings because some Laravel rules, such as
database-backed `exists` or conditional `required_if`, cannot be represented
fully in portable JSON Schema.

## Snapshot Compatibility

Create a snapshot before changing contract rules:

```bash
php artisan message-contracts:snapshot --output=message-contracts.snapshot.json
```

Compare the current registered contracts against that snapshot:

```bash
php artisan message-contracts:check-breaking-changes \
  --against=message-contracts.snapshot.json
```

Treat compatibility warnings as failures:

```bash
php artisan message-contracts:check-breaking-changes \
  --against=message-contracts.snapshot.json \
  --fail-on-warning
```

## Exit Codes

| Command | Exit code | Meaning |
| --- | ---: | --- |
| `message-contracts:validate` | `0` | Payload or envelope is valid. |
| `message-contracts:validate` | `1` | Validation failed or input was missing. |
| `message-contracts:validate-examples` | `0` | All contract examples passed validation. |
| `message-contracts:validate-examples` | `1` | One or more examples failed, or a required example was missing. |
| `message-contracts:check-breaking-changes` | `2` | Snapshot file was not found. |
| `message-contracts:check-breaking-changes` | `3` | Breaking changes were detected. |
| `message-contracts:check-breaking-changes` | `4` | Warnings failed because `--fail-on-warning` was used. |
| `message-contracts:export-json-schema` | `4` | Schema warnings failed because `--fail-on-warning` was used. |

## Package Quality Checks (Contributors)

The following commands are for **package contributors**, not application users.

```bash
composer test
composer format:test
composer analyse
composer refactor:check
composer ci
```

`composer ci` runs Pint, Rector dry-run, PHPStan, and Pest.



---

**Previous:** [Output formats](output.md) | **Next:** [CI](ci.md)
