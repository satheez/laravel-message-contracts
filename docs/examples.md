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
use Satheez\MessageContracts\DTO\Message;

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
use App\MessageContracts\UserRegisteredV1Message;

class UserObserver
{
    public function created(User $user)
    {
        // Construct the message DTO
        $message = UserRegisteredV1Message::message([
            'user_id' => $user->id,
            'email'   => $user->email,
            'registered_at' => $user->created_at->toISOString(),
        ]);

        // Push to an exchange (pseudocode — replace with your broker client)
        MyRabbitMQClient::publish('events_exchange', 'user.registered', $message->toJson());
    }
}
```

---

## 5. Using with Laravel Jobs & Queues

This is the most common integration pattern for Laravel applications. The
contract validates the payload at dispatch time, and the job re-validates it on
the consumer side for safety.

### Define the Contract

```php
namespace App\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class SendWelcomeEmailV1Message extends MessageContract
{
    public static function contract(): string
    {
        return 'email.send-welcome';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function rules(): array
    {
        return [
            'user_id'    => ['required', 'integer'],
            'email'      => ['required', 'email'],
            'first_name' => ['required', 'string', 'max:255'],
        ];
    }

    public static function example(): array
    {
        return [
            'user_id'    => 42,
            'email'      => 'jane@example.com',
            'first_name' => 'Jane',
        ];
    }
}
```

### Create a Job That Carries the Contract Message

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Satheez\MessageContracts\DTO\Message;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly string $messageJson;

    /**
     * Accept the pre-validated Message DTO and store its JSON.
     */
    public function __construct(Message $message)
    {
        // Serialize the contract message to JSON for queue transport.
        // The Message DTO is readonly and already validated at dispatch time.
        $this->messageJson = $message->toJson();
    }

    /**
     * Process the job on the consumer side.
     */
    public function handle(): void
    {
        // 1. Parse the envelope back into a Message DTO
        $message = Message::fromJson($this->messageJson);

        // 2. Re-validate the payload against the registered contract
        $message->validateOrFail();

        // 3. Use the validated payload safely
        $email     = $message->payload('email');
        $firstName = $message->payload('first_name');

        // 4. Execute business logic
        Mail::to($email)->send(new WelcomeEmail($firstName));

        logger()->info('Welcome email sent', [
            'contract' => $message->contract(),
            'version'  => $message->version(),
            'user_id'  => $message->payload('user_id'),
        ]);
    }
}
```

### Dispatch from a Controller

```php
use App\Jobs\SendWelcomeEmailJob;
use App\MessageContracts\SendWelcomeEmailV1Message;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->validated());

        // Build a validated contract message (validates at creation time)
        $message = SendWelcomeEmailV1Message::message(
            payload: [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'first_name' => $user->first_name,
            ],
            meta: [
                'triggered_by' => 'registration',
            ],
        );

        // Dispatch the job — the validated message travels through the queue
        SendWelcomeEmailJob::dispatch($message);

        return response()->json(['status' => 'registered']);
    }
}
```

> **Why serialize to JSON in the constructor?** Laravel serializes job
> properties for queue storage. Storing the JSON string keeps the payload
> transport-safe and avoids serialization issues with the readonly `Message`
> DTO.

---

## 6. Cross-Service Job Pattern

When Service A dispatches a message and Service B consumes it, both services
register the same contract class independently. Service B uses a generic job
that routes by contract name.

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Satheez\MessageContracts\DTO\Message;

class ProcessContractMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $rawJson) {}

    public function handle(): void
    {
        $message = Message::fromJson($this->rawJson);
        $message->validateOrFail();

        // Route based on contract name and version
        match ($message->contract()) {
            'email.send-welcome' => $this->handleWelcomeEmail($message),
            'order.created'      => $this->handleOrderCreated($message),
            default              => logger()->warning('Unknown contract', [
                'contract' => $message->contract(),
                'version'  => $message->version(),
            ]),
        };
    }

    private function handleWelcomeEmail(Message $message): void
    {
        Mail::to($message->payload('email'))
            ->send(new WelcomeEmail($message->payload('first_name')));
    }

    private function handleOrderCreated(Message $message): void
    {
        // Process order...
    }
}
```

---

## 7. Webhook Receiver

When your application receives webhook payloads from an external system, you can
validate them against a contract before processing.

```php
use App\MessageContracts\StripePaymentSucceededV1Message;
use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\Exceptions\MessageValidationException;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        // Build a message from the incoming webhook payload
        $message = StripePaymentSucceededV1Message::message(
            payload: $request->input('data'),
        );

        try {
            $message->validateOrFail();
        } catch (MessageValidationException $e) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        // Process validated payload
        $paymentId = $message->payload('payment_id');
        $amount    = $message->payload('amount');

        // ...

        return response()->json(['status' => 'ok']);
    }
}
```


---

**Previous:** [Comparison](comparison.md) | **Next:** [FAQ](faq.md)
