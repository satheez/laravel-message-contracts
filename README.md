# Laravel Message Contracts

A Laravel package for defining, validating, versioning, and serializing message contracts shared between microservices or asynchronous systems.

[![CI](https://github.com/satheez/laravel-message-contracts/actions/workflows/ci.yml/badge.svg)](https://github.com/satheez/laravel-message-contracts/actions)

**Note:** This package is completely transport-agnostic. It does not replace your message broker or queue client (RabbitMQ, SQS, Kafka, Redis, etc.). Instead, it ensures the *payloads* inside your messages are strict, validated, and safely versioned.

## 🚀 Quick Links
- [Detailed Usage Guide](docs/usage.md)
- [Examples & Recipes](docs/examples.md)

## 📦 Installation

Require the package via Composer:

```bash
composer require satheez/laravel-message-contracts
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=message-contracts-config
```

## ✨ Why use this package?

In microservice architectures, services often exchange JSON payloads. Without formal contracts, a producer might change a field name, silently breaking the consumer service.

**Laravel Message Contracts** solves this by providing:

1. **Strict Structure**: Define payloads as PHP classes with built-in Laravel validation rules.
2. **Versioning**: Safely introduce `V1`, `V2` contracts without breaking older consumers.
3. **Producer & Consumer Validation**: Catch bad data before it leaves your app, and reject bad data before it enters your business logic.
4. **Standardized Envelope**: Automatically wrap your business payloads in a standard `{ "contract": "...", "version": 1, "payload": {...} }` format.

## ⚡ Quick Start

### 1. Create a Contract

Generate a new contract using Artisan:

```bash
php artisan make:message-contract UserRegistered --contract-version=1
```

Define its validation rules:

```php
namespace App\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class UserRegisteredV1Message extends MessageContract
{
    public static function contract(): string
    {
        return 'user.registered';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'email'   => ['required', 'email'],
        ];
    }
}
```

### 2. Register the Contract

Add it to your `config/message-contracts.php`:

```php
'contracts' => [
    App\MessageContracts\UserRegisteredV1Message::class,
],
```

### 3. Producer Side: Create & Validate

When dispatching an event, create a `Message` DTO from your contract. This automatically validates the outgoing payload.

```php
use App\MessageContracts\UserRegisteredV1Message;

$message = UserRegisteredV1Message::message([
    'user_id' => 123,
    'email'   => 'john@example.com',
]);

// Convert to JSON and send it via your queue/broker of choice
$json = $message->toJson();
RabbitMQ::publish($json);
```

### 4. Consumer Side: Receive & Validate

When receiving a message, parse and validate it before processing.

```php
use Satheez\MessageContracts\DTO\Message;

$json = $request->getContent(); // or from queue payload

$message = Message::fromJson($json);

// Resolves the correct contract (e.g. user.registered v1) and validates it
$message->validateOrFail();

$userId = $message->payload('user_id');
```

## 🛠 Artisan Commands

- `php artisan make:message-contract Name` - Scaffold a new contract.
- `php artisan message-contracts:list` - See all registered contracts.
- `php artisan message-contracts:validate` - Validate raw JSON against a contract.
- `php artisan message-contracts:validate-examples` - Validate all `example()` arrays defined in your contracts.
- `php artisan message-contracts:export-json-schema` - Export registered message contracts as JSON Schema files.
- `php artisan message-contracts:snapshot` - Create a snapshot of all registered message contracts.
- `php artisan message-contracts:check-breaking-changes` - Check for breaking changes against a previous snapshot.

## 📑 JSON Schema Generation

You can export your message contracts as JSON Schema files. This is useful for sharing schemas with consumer services written in other languages or generating documentation.

```bash
php artisan message-contracts:export-json-schema --output=docs/schemas
```

## 🛡️ Preventing Breaking Changes

When versioning message contracts, it's crucial to ensure that you don't inadvertently introduce breaking changes (e.g., removing a required field, narrowing a type constraint) without bumping the version number.

You can create a snapshot of your contracts:

```bash
php artisan message-contracts:snapshot
```

And later, in your CI pipeline, verify that no breaking changes were introduced:

```bash
php artisan message-contracts:check-breaking-changes
```

## 🧪 Testing

The package provides a `MessageAssert` helper for your Pest or PHPUnit tests:

```php
use Satheez\MessageContracts\Testing\MessageAssert;

MessageAssert::assertValid(UserRegisteredV1Message::class, [
    'user_id' => 1,
    'email' => 'test@example.com',
]);
```

## 📡 AsyncAPI Generation

You can generate comprehensive **AsyncAPI 2.6.0** documentation for your message contracts. 

```bash
php artisan message-contracts:export-asyncapi
```

To enrich the generated documentation, you can optionally override methods in your `MessageContract`:
```php
public static function title(): ?string { return 'User Registered Event'; }
public static function channel(): ?string { return 'users.events'; }
public static function direction(): string { return 'publish'; } // publish, subscribe, both
public static function tags(): array { return ['Users', 'Billing']; }
```

## 🗃️ Spatie Laravel Data Integration

If you already use the excellent `spatie/laravel-data` package, you don't need to write validation rules twice. Simply extend `DataPayloadContract`:

```php
use Satheez\MessageContracts\SpatieData\DataPayloadContract;
use App\Data\UserData;

final class UserRegisteredV1Message extends DataPayloadContract
{
    public static function contract(): string { return 'user.registered'; }
    public static function version(): int { return 1; }
    
    // The validation and creation logic is automatically delegated to your Data object!
    public static function dataClass(): string { return UserData::class; }
}
```
The internal validator will natively run `UserData::validateAndCreate($payload)`.

---
See the [Documentation](docs/usage.md) for full configuration and advanced usage.
