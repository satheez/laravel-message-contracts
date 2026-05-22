# Output

This page shows the main output formats produced by the package: message
envelopes, command output, JSON Schema, AsyncAPI, and compatibility snapshots.

## Message Envelope

Producer code creates a `Message` DTO through the contract class:

```php
$message = UserRegisteredV1Message::message(
    payload: [
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-22T04:45:00Z',
    ],
    meta: [
        'trace_id' => 'req-7f1c4c2a',
    ],
);

// Pass pretty: true for human-readable output (useful for debugging and docs).
$json = $message->toJson(pretty: true);
```

Default output:

```json
{
  "contract": "user.registered",
  "version": 1,
  "payload": {
    "user_id": 123,
    "email": "john@example.com",
    "registered_at": "2026-05-22T04:45:00Z"
  },
  "meta": {
    "message_id": "01JWGJ8FGM7X8Y5H0R2M6W9S4D",
    "created_at": "2026-05-22T04:45:00.000000Z",
    "trace_id": "req-7f1c4c2a"
  }
}
```

`meta` is omitted when it is empty.

## Contract List JSON

```bash
php artisan message-contracts:list --format=json
```

Example output:

```json
[
  {
    "contract": "user.registered",
    "version": 1,
    "class": "App\\MessageContracts\\UserRegisteredV1Message",
    "deprecated": false,
    "has_example": true,
    "rules_count": 4
  }
]
```

## JSON Schema

Export payload schemas:

```bash
php artisan message-contracts:export-json-schema --output=docs/schemas --pretty
```

Payload schema files are named:

```text
{contract}.v{version}.schema.json
```

Example:

```text
docs/schemas/user.registered.v1.schema.json
```

When `--include-message-envelope` is used, a second envelope schema is written:

```text
docs/schemas/user.registered.v1.message.schema.json
```

## AsyncAPI

Export AsyncAPI as JSON:

```bash
php artisan message-contracts:export-asyncapi \
  --format=json \
  --output=docs/asyncapi.json
```

Export AsyncAPI as YAML:

```bash
php artisan message-contracts:export-asyncapi \
  --format=yaml \
  --output=docs/asyncapi.yaml
```

AsyncAPI output uses version `2.6.0` and groups messages by the contract
`channel()` when provided. If no channel is provided, it uses the contract name.

Direction maps to AsyncAPI operations:

| Contract `direction()` | AsyncAPI operation |
| --- | --- |
| `publish` | `subscribe` |
| `subscribe` | `publish` |
| `both` | Both operations |

This follows AsyncAPI semantics: if the application publishes a message,
consumers subscribe to it.

## Snapshot

Create a snapshot:

```bash
php artisan message-contracts:snapshot --output=message-contracts.snapshot.json
```

Snapshot files contain:

```json
{
  "generated_at": "2026-05-22T04:45:00.000000Z",
  "package": "satheez/laravel-message-contracts",
  "contracts": [
    {
      "contract": "user.registered",
      "version": 1,
      "class": "App\\MessageContracts\\UserRegisteredV1Message",
      "deprecated": false,
      "rules": {},
      "schema": {}
    }
  ]
}
```

Use snapshots with `message-contracts:check-breaking-changes` to detect
incompatible changes before consumers receive them.



---

**Previous:** [Architecture](architecture.md) | **Next:** [Checks](checks.md)
