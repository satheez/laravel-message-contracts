# Installation

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `^8.2` |
| Laravel | `^10.0`, `^11.0`, `^12.0`, or `^13.0` |

## Install With Composer

```bash
composer require satheez/laravel-message-contracts
```

Laravel package discovery registers the service provider automatically.

## Publish Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag=message-contracts-config
```

This creates:

```text
config/message-contracts.php
```

> **Note:** The package works without publishing the config — sensible defaults
> are applied. Publish only when you need to customize strict mode, metadata,
> envelope keys, or schema export settings.

## Publish Stubs

Publishing stubs is optional. Use it when your application wants to customize
the generated contract class template.

```bash
php artisan vendor:publish --tag=message-contracts-stubs
```

This creates:

```text
stubs/message-contract.stub
```

## Create a Contract

Generate a contract class:

```bash
php artisan make:message-contract UserRegistered \
  --contract-version=1 \
  --contract=user.registered
```

The default output path is controlled by `contracts_path`, and the namespace is
controlled by `contracts_namespace`.

## Register the Contract

Add the generated class to `config/message-contracts.php`:

```php
'contracts' => [
    App\MessageContracts\UserRegisteredV1Message::class,
],
```

Confirm it is registered:

```bash
php artisan message-contracts:list
```

## First Validation

After adding rules that match your payload, validate a payload inline:

```bash
php artisan message-contracts:validate user.registered \
  --contract-version=1 \
  --json='{"user_id":123,"email":"john@example.com","registered_at":"2026-05-22T04:45:00Z"}'
```

If your contract rules require different fields, adjust the JSON to match the
contract.

> **Tip:** For complex payloads, use `--file=payload.json` instead of inline
> `--json` to avoid shell escaping issues.

## Optional: Export Documentation Artifacts

Export JSON Schema:

```bash
php artisan message-contracts:export-json-schema --output=docs/schemas --pretty
```

Export AsyncAPI:

```bash
php artisan message-contracts:export-asyncapi --format=yaml --output=docs/asyncapi.yaml
```


---

**Next:** [Usage guide](usage.md)
