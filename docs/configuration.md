# Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --tag=message-contracts-config
```

The file is published to `config/message-contracts.php`.

## Options

| Option | Default | Purpose |
| --- | --- | --- |
| `contracts_path` | `app/MessageContracts` | Directory used by `make:message-contract`. |
| `contracts_namespace` | `App\MessageContracts` | Namespace used for generated contract classes. |
| `strict` | `true` | Reject payload keys that are not declared in `rules()`. |
| `validate_outgoing` | `true` | Validate payloads created with `MessageContract::message()`. |
| `validate_incoming` | `true` | Reserved for future use. Currently has no effect on incoming validation — call `validate()` or `validateOrFail()` explicitly on the consumer side. |
| `message_keys` | `contract`, `version`, `payload`, `meta` | Serialized envelope key names. |
| `meta` | ULID and timestamp enabled | Automatic metadata added to producer messages. |
| `contracts` | `[]` | Registered contract classes. |
| `json_schema` | Enabled | JSON Schema export settings. |

## Registering Contracts

Add every contract class that should be resolvable by incoming messages:

```php
'contracts' => [
    App\MessageContracts\UserRegisteredV1Message::class,
    App\MessageContracts\UserRegisteredV2Message::class,
    App\MessageContracts\OrderCreatedV1Message::class,
],
```

The registry resolves messages by `contract` and `version`.

## Strict Mode

Strict mode rejects extra top-level payload fields:

```php
'strict' => true,
```

Keep strict mode enabled for new integrations. Disable it only when an existing
producer sends extra fields that cannot be removed yet:

```php
'strict' => false,
```

## Outgoing Validation

Producer-side validation runs when you call `MessageContract::message()`:

```php
'validate_outgoing' => true,
```

Disabling this lets invalid messages be serialized, so only turn it off for a
controlled migration or a benchmark where validation is handled elsewhere.

> **Tip:** Use environment variables to toggle validation per environment:
>
> ```php
> 'validate_outgoing' => env('MESSAGE_CONTRACTS_VALIDATE_OUTGOING', true),
> 'strict' => env('MESSAGE_CONTRACTS_STRICT', true),
> ```
>
> This lets you relax strict mode in local development while keeping it enforced
> in CI and production.

## Message Envelope Keys

The default serialized message is:

```json
{
  "contract": "user.registered",
  "version": 1,
  "payload": {},
  "meta": {}
}
```

If a legacy system expects different top-level keys, customize `message_keys`:

```php
'message_keys' => [
    'contract' => 'type',
    'version' => 'schema_version',
    'payload' => 'data',
    'meta' => 'metadata',
],
```

The `Message` DTO will read and write those configured keys.

## Metadata

By default, producer messages include `message_id` and `created_at`:

```php
'meta' => [
    'include_message_id' => true,
    'include_created_at' => true,
    'message_id_strategy' => 'ulid',
    'include_source' => false,
    'source' => env('APP_NAME', 'laravel'),
],
```

Use UUIDs instead of ULIDs:

```php
'message_id_strategy' => 'uuid',
```

Include the application source:

```php
'include_source' => true,
'source' => env('APP_NAME', 'billing-service'),
```

Caller-supplied metadata overrides automatic metadata with the same key.

## JSON Schema Export

Schema export is configured under `json_schema`:

```php
'json_schema' => [
    'enabled' => true,
    'output_path' => base_path('docs/schemas'),
    'draft' => '2020-12',
    'pretty' => true,
    'additional_properties' => false,
    'include_examples' => true,
    'fail_on_unsupported_rules' => false,
    'id_base_url' => null,
],
```

Set `id_base_url` when schemas will be hosted at stable URLs:

```php
'id_base_url' => 'https://example.com/schemas',
```

Set `fail_on_unsupported_rules` to `true` when CI should fail if Laravel rules
cannot be mapped fully to JSON Schema.

## Stubs

Publish the message contract stub when you want to customize generated classes:

```bash
php artisan vendor:publish --tag=message-contracts-stubs
```

The command publishes `resources/stubs/message-contract.stub` to
`stubs/message-contract.stub` in the application.



---

**Previous:** [Usage guide](usage.md) | **Next:** [Architecture](architecture.md)
