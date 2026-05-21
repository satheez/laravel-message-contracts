# Examples & Recipes

This page covers common use cases and patterns for utilizing Laravel Message Contracts in your projects.

---

## 1. Handling Multiple Versions

When your application evolves, you often need to change a payload. Because consumers might not update immediately, it's safer to release a `V2` contract and allow consumers to support both until the transition is complete.

### Step 1: Create the V2 Contract

Generate the new version:

```bash
php artisan make:message-contract UserRegistered --contract-version=2
```

Define the differences (e.g., adding a required `locale` field):

```php
namespace App\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class UserRegisteredV2Message extends MessageContract
{
    public static function contract(): string
    {
        return 'user.registered'; // The logical name remains the same
    }

    public static function version(): int
    {
        return 2; // Version is incremented
    }

    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'email'   => ['required', 'email'],
            'locale'  => ['required', 'string', 'size:2'], // NEW FIELD
        ];
    }
}
```

### Step 2: Register Both Contracts

Register both versions in `config/message-contracts.php`:

```php
'contracts' => [
    App\MessageContracts\UserRegisteredV1Message::class,
    App\MessageContracts\UserRegisteredV2Message::class,
],
```

### Step 3: Consumer Logic

When the consumer receives a `user.registered` message, the package's `MessageContractRegistry` automatically matches the incoming `"version"` key to the correct Contract class.

```php
$message = Message::fromJson($json);
$message->validateOrFail();

if ($message->version() === 2) {
    // Handle V2 logic
    $locale = $message->payload('locale');
} else {
    // Handle V1 fallback logic
    $locale = 'en'; // Default
}
```

---

## 2. Using in Tests

The package provides a built-in `MessageAssert` class that makes writing Pest or PHPUnit tests a breeze.

### Asserting Valid Payloads

You can use the helper to verify that a payload dictionary successfully passes a contract's rules.

```php
use Satheez\MessageContracts\Testing\MessageAssert;
use App\MessageContracts\OrderCreatedV1Message;

it('accepts a valid order payload', function () {
    MessageAssert::assertValid(OrderCreatedV1Message::class, [
        'order_id' => 10,
        'total'    => 50.00,
        'currency' => 'EUR',
    ]);
});
```

### Asserting Invalid Payloads

You can also ensure that invalid data correctly fails validation, protecting your system from bad inputs.

```php
it('rejects a negative order total', function () {
    MessageAssert::assertInvalid(OrderCreatedV1Message::class, [
        'order_id' => 10,
        'total'    => -5.00, // Should fail the min:0 rule
        'currency' => 'EUR',
    ]);
});
```

### Asserting Specific Errors

If you want to be precise about *which* fields failed validation:

```php
it('fails when currency is missing', function () {
    MessageAssert::assertInvalidFields(
        OrderCreatedV1Message::class, 
        [
            'order_id' => 10,
            'total'    => 50.00,
        ], 
        ['currency'] // We expect an error on the 'currency' key
    );
});
```

---

## 3. Validating Examples via CLI

If you provide an `example()` payload inside your contracts, you can run a CLI command to automatically validate all of them. This is a great addition to your CI/CD pipeline to ensure your documentation examples never drift from the validation rules.

```bash
php artisan message-contracts:validate-examples
```

**Output:**
```
Validating examples for 2 contracts...
✔ user.registered (v1) - passed
✔ order.created (v1) - passed

All contract examples are valid.
```

---

## 4. Enveloping Existing Data (Producer Side)

If you have existing event listeners that hook into Eloquent events, you can quickly wrap the model data into a strict contract before sending it to a broker.

```php
class UserObserver
{
    public function created(User $user)
    {
        // Construct the message DTO
        $message = UserRegisteredV1Message::message([
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);
        
        // Push to an exchange
        MyRabbitMQClient::publish('events_exchange', 'user.registered', $message->toJson());
    }
}
```
