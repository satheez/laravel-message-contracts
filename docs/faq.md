# FAQ

## Does this package replace my queue or broker?

No. It validates and documents the JSON payload that moves through your
transport. Keep using RabbitMQ, SQS, Kafka, Redis, Laravel queues, webhooks, or
any other delivery mechanism.

## How do I use this with Laravel Jobs?

You inject the serialized message JSON into your job's constructor, and parse/validate it in the job's `handle()` method. See the [Laravel Jobs & Queues](examples.md#5-using-with-laravel-jobs--queues) recipe for a complete example.

## Should I include the version in the contract name?

No. Keep `contract()` stable and put the version in `version()`.

```php
public static function contract(): string
{
    return 'user.registered';
}

public static function version(): int
{
    return 1;
}
```

Use `UserRegisteredV1Message`, `UserRegisteredV2Message`, and similar class
names to make versions obvious in PHP code.

## When should I create a new version?

Create a new version for breaking payload changes, including:

- Removing a field.
- Renaming a field.
- Changing a field type.
- Adding a new required field.
- Making an optional field required.
- Removing accepted enum values.

Adding a new optional field is usually safe, but consumers still need to ignore
unknown data or opt into the new version intentionally.

## Can I register multiple versions at the same time?

Yes. Register each version in `config/message-contracts.php`:

```php
'contracts' => [
    App\MessageContracts\UserRegisteredV1Message::class,
    App\MessageContracts\UserRegisteredV2Message::class,
],
```

The registry resolves the correct class from the incoming `contract` and
`version` values.

## Does `Message::fromJson()` validate the payload?

No. It only parses the envelope. Call `validate()` or `validateOrFail()` before
processing:

```php
$message = Message::fromJson($json);
$message->validateOrFail();
```

## What is the performance impact?

Validation adds a few milliseconds per message. For high-throughput systems where producer performance is critical, you can set `'validate_outgoing' => false` in your configuration to skip validation when creating the message, and rely solely on consumer-side validation.

## Can I validate nested objects and arrays?

Yes, because contracts use standard Laravel validation rules, you can use dot-notation for nested fields and wildcards for arrays:

```php
public static function rules(): array
{
    return [
        'order_id' => ['required', 'integer'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'integer'],
        'items.*.quantity' => ['required', 'integer', 'min:1'],
        'customer.email' => ['required', 'email'],
    ];
}
```

## Why does strict mode reject my payload?

Strict mode only allows keys declared in `rules()`. If the payload includes an
extra key, validation fails. Add the field to `rules()` when it is part of the
contract, or disable strict mode temporarily for legacy producers.

## Can I customize the message envelope keys?

Yes. Configure `message_keys` in `config/message-contracts.php`. This is useful
when an existing system expects names such as `type`, `schema_version`, `data`,
or `metadata`.

## Can non-Laravel consumers use these contracts?

Yes. Export JSON Schema for non-Laravel consumers:

```bash
php artisan message-contracts:export-json-schema --output=docs/schemas --pretty
```

Publish those schemas in your docs, package them with SDKs, or attach them as
build artifacts.

## What happens when Laravel rules cannot be mapped to JSON Schema?

The schema exporter emits warnings for rules that cannot be represented
portably, such as database-backed or conditional rules. Use `--fail-on-warning`
in CI if every exported schema must be complete.

## Can I use Spatie Laravel Data?

Yes. Extend `DataPayloadContract` and return the data class:

```php
use App\Data\UserData;
use Satheez\MessageContracts\SpatieData\DataPayloadContract;

final class UserRegisteredV1Message extends DataPayloadContract
{
    public static function contract(): string
    {
        return 'user.registered';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
```

When the data class supports `validateAndCreate()`, validation is delegated to
the data object.

On the consumer side, you can retrieve the strongly-typed Data object like this:

```php
$message = Message::fromJson($json);
$message->validateOrFail(); // Validates using Spatie Data rules

// Resolve the Data object
$userData = app(MessageContractRegistry::class)
    ->resolve($message->contract(), $message->version())::dataClass()::from($message->payload());

echo $userData->email;
```



---

**Previous:** [Examples and recipes](examples.md)
