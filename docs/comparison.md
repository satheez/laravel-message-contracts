# Comparison

Laravel Message Contracts is a Laravel-native contract layer for JSON message
payloads. It is useful when you want strict, versioned payload validation
without moving every service to a separate schema language or broker-specific
message framework.

## Comparisons

### Plain Arrays in Events or Jobs
* **Works well for:** Small apps and private messages.
* **Common gap:** Payload shape is implicit and easy to drift.
* **What this package adds:** Named, versioned contracts with strict validation.

### Laravel built-in `$job->payload()`
* **Works well for:** Inspecting the internal structure of a queued job (e.g. knowing the queue connection or max tries).
* **Common gap:** It gives you the wrapper used by Laravel's queue worker, not a validated boundary for your business data.
* **What this package adds:** Focuses strictly on validating and versioning the *business payload* you pass into the job.

### Manual `Validator::make()` calls
* **Works well for:** One-off producer or consumer validation.
* **Common gap:** Rules are duplicated across producers, consumers, tests, and docs.
* **What this package adds:** One reusable class for validation, examples, schemas, and docs.

### Laravel Form Requests
* **Works well for:** HTTP request validation.
* **Common gap:** Form Requests are tied to controller input, not queue or broker payloads.
* **What this package adds:** Laravel validation rules for transport-agnostic messages.

### Hand-written JSON Schema
* **Works well for:** Polyglot consumers and external validators.
* **Common gap:** Schema files can drift from Laravel runtime rules.
* **What this package adds:** JSON Schema exported directly from the contract class.

### AsyncAPI written by hand
* **Works well for:** Message documentation portals.
* **Common gap:** Documentation often lags behind implemented payloads.
* **What this package adds:** AsyncAPI generated from registered contracts.

### Avro, Protobuf, or Schema Registries
* **Works well for:** Large streaming platforms with strong cross-language governance.
* **Common gap:** Requires more infrastructure and a separate schema language.
* **What this package adds:** A smaller Laravel-first option for JSON messages.

### Broker-specific message classes
* **Works well for:** Deep integration with one queue or stream.
* **Common gap:** Payload design becomes coupled to the transport.
* **What this package adds:** Payload contracts stay portable across queues, brokers, webhooks, and events.

## When This Package Fits

Use it when:

- Laravel services publish or consume structured JSON.
- Payloads are shared between teams, services, or languages.
- You want producer and consumer validation from the same contract class.
- You need `V1`, `V2`, and later versions to coexist during migrations.
- You want JSON Schema or AsyncAPI output from Laravel validation rules.
- You want compatibility checks in CI without adding a schema registry.

## When Another Tool Is Better

Choose another approach when:

- Your organization already standardizes on Avro, Protobuf, or a schema registry.
- You need binary serialization or generated clients from an IDL.
- You need delivery guarantees, retries, ordering, transactions, or outbox storage.
- The payload never leaves one application boundary.
- A message is better represented as a simple value object inside one codebase.

## What It Does Not Replace

This package does not replace:

- RabbitMQ, SQS, Kafka, Redis, Laravel queues, or webhooks.
- An outbox table or distributed transaction pattern.
- Observability, tracing, retries, dead-letter handling, or idempotency.
- Domain event design.
- API versioning for HTTP endpoints.

It sits at the payload boundary: define the message body, validate it, document
it, and detect incompatible shape changes.



---

**Previous:** [CI](ci.md) | **Next:** [Examples and recipes](examples.md)
