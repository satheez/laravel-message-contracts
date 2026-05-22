# Usage Guide

## Defining Message Contracts

A Message Contract is a plain PHP class extending `Satheez\MessageContracts\Contracts\MessageContract`. It acts as the single source of truth for a specific payload shape.

### Generating a Contract

You can generate a contract using the Artisan command:

```bash
php artisan make:message-contract OrderCreated --contract-version=1
```

This will create `app/MessageContracts/OrderCreatedV1Message.php`.

### Required Methods

Your contract must implement three static methods:

```php
namespace App\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class OrderCreatedV1Message extends MessageContract
{
    public static function contract(): string
    {
        // A unique logical name for this message
        return 'order.created';
    }

    public static function version(): int
    {
        // The integer version number
        return 1;
    }

    public static function rules(): array
    {
        // Standard Laravel validation rules
        return [
            'order_id' => ['required', 'integer'],
            'total'    => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}
```

### Optional Methods

You can provide additional context using optional methods:

```php
    public static function example(): array
    {
        return [
            'order_id' => 12345,
            'total'    => 199.99,
            'currency' => 'USD',
        ];
    }

    public static function description(): ?string
    {
        return 'Fired when a user successfully checks out an order.';
    }

    public static function deprecated(): bool
    {
        // Set to true if you are migrating consumers to V2
        return false;
    }
```

---

## The Contract Registry

For the package to recognize your contracts, you must register them in `config/message-contracts.php`.

```php
return [
    'contracts' => [
        App\MessageContracts\UserRegisteredV1Message::class,
        App\MessageContracts\OrderCreatedV1Message::class,
    ],
];
```

You can view all registered contracts by running:

```bash
php artisan message-contracts:list
```

---

## Validation & Strict Mode

By default, the package runs in **Strict Mode**. This means if a payload contains a field that is *not* explicitly defined in the `rules()` array, validation will fail.

You can turn this off in `config/message-contracts.php`:

```php
'strict' => false,
```

However, keeping strict mode enabled is highly recommended as it prevents leaking accidental data across service boundaries.

---

## Producer Side: Sending Messages

When your application is the producer (the one dispatching the message), you should construct the payload using the Contract class. This guarantees it validates before being sent.

```php
use App\MessageContracts\OrderCreatedV1Message;
use Satheez\MessageContracts\Exceptions\MessageValidationException;

// 1. Create the message DTO (validates automatically)
$message = OrderCreatedV1Message::message(
    payload: [
        'order_id' => 12345,
        'total'    => 199.99,
        'currency' => 'USD',
    ],
    meta: [
        'trace_id' => request()->header('X-Trace-Id'),
    ],
);

// 2. Serialize and send
$json = $message->toJson();
// Send $json to RabbitMQ, SQS, a Laravel Job, etc.
```

> **Note:** `MessageContract::message()` validates the payload before building
> the `Message` DTO (when `validate_outgoing` is enabled). If validation fails,
> a `MessageValidationException` is thrown immediately so invalid messages never
> reach the transport.

The output JSON will look like this:

```json
{
  "contract": "order.created",
  "version": 1,
  "payload": {
    "order_id": 12345,
    "total": 199.99,
    "currency": "USD"
  },
  "meta": {
    "message_id": "01JWGJ8FGM7X8Y5H0R2M6W9S4D",
    "created_at": "2026-05-22T04:45:00.000000Z",
    "trace_id": "ab12-cd34-ef56"
  }
}
```

---

## Consumer Side: Receiving Messages

When your application receives a message from a broker, it needs to parse and validate it.

### From JSON

```php
use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\Exceptions\MessageValidationException;

// 1. You receive a raw JSON string from your queue
$json = '{"contract":"order.created","version":1,"payload":{"order_id":123}}';

// 2. Parse it into a Message DTO
$message = Message::fromJson($json);

try {
    // 3. Validate it against the registry
    $message->validateOrFail();

    // 4. Safely access data
    $orderId = $message->payload('order_id');

} catch (MessageValidationException $e) {
    // The payload was invalid or the contract is not registered.
    // Log the error and perhaps send the message to a Dead Letter Queue.
    Log::error('Invalid message received', ['errors' => $e->getErrors()]);
}
```

### From an Array

Some brokers (e.g., SQS with automatic JSON decoding) give you a decoded array
instead of a raw string:

```php
$message = Message::fromArray($decodedArray);
$message->validateOrFail();
```

### Non-Throwing Validation

Use `validate()` instead of `validateOrFail()` when you want to inspect the
result without catching an exception:

```php
$result = $message->validate();

if ($result->failed()) {
    Log::warning('Validation failed', ['errors' => $result->errors()]);
    return;
}

$orderId = $message->payload('order_id');
```

### Accessing Data

The `Message` object provides helper methods to access your data:

```php
$message->contract();         // 'order.created'
$message->version();          // 1

$message->payload();          // Returns the full payload array
$message->payload('user_id'); // Returns a specific field
$message->payload('address.city', 'N/A'); // Supports dot-notation and defaults

$message->meta('trace_id');   // Access metadata
```


---

**Previous:** [Installation](installation.md) | **Next:** [Configuration](configuration.md)
