# Implementation Plan: Laravel Message Contracts

## 1. Summary of Package Purpose
`laravel-message-contracts` is a transport-agnostic Laravel package for defining, validating, versioning, serializing, testing, and documenting structured cross-service message contracts. It aims to solve the problem of inconsistent, undocumented, and breaking payload changes in microservice communications without coupling to any specific transport layer like RabbitMQ, Kafka, or SQS.

## 2. Key Decisions Extracted from Docs
- **Naming Migration:** The package will use the `laravel-message-contracts` name, and terminology will be updated from `PayloadContract` to `MessageContract`, `PayloadMessage` to `Message`, etc., as per user preference.
- **Transport Agnostic:** The core will only validate, serialize, and structure the data. How it's sent/received is up to the application.
- **Validation:** Validation will utilize Laravel's built-in validation system. We will support producer-side (outgoing) and consumer-side (incoming) validation.
- **Standard Format:** Serialized messages will consistently have a `contract`, `version`, and `payload` top-level keys, with an optional `meta` key.
- **Registry:** A registry will map `contract` + `version` combinations to concrete PHP contract classes.

## 3. MVP Scope
- Base `MessageContract` class for defining contracts.
- `Message` DTO wrapper.
- Contract versioning support.
- Producer-side and Consumer-side payload validation using Laravel Validator.
- Message Serialization (`toArray`, `toJson`) and Parsing (`fromArray`, `fromJson`).
- Contract Registry.
- Testing Helpers (`MessageAssert`).
- Artisan Commands: `make:message-contract`, `message-contracts:list`, `message-contracts:validate`, `message-contracts:validate-examples`.
- Package Service Provider and Configuration.

## 4. Explicit Non-Goals
- Transport integrations (no RabbitMQ, Kafka, SQS, or Laravel Queue clients).
- Exporting to JSON Schema or AsyncAPI (post-MVP).
- Breaking change detector (post-MVP).
- Database-backed schema registries.
- UI Dashboards.
- Spatie Laravel Data or CloudEvents integrations out of the box for MVP.

## 5. Proposed Package Structure
```text
config/
  message-contracts.php
src/
  MessageContractsServiceProvider.php
  Contracts/
    MessageContract.php
  DTO/
    Message.php
    MessageValidationResult.php
  Registry/
    MessageContractRegistry.php
  Validation/
    MessageValidator.php
  Serialization/
    MessageSerializer.php
    MessageParser.php
  Exceptions/
    MessageContractsException.php
    ConfigurationException.php
    InvalidMessageContractException.php
    DuplicateMessageContractException.php
    UnknownMessageContractException.php
    UnknownMessageContractVersionException.php
    MessageValidationException.php
    InvalidMessageException.php
    MessageSerializationException.php
    MessageParsingException.php
  Console/Commands/
    MakeMessageContractCommand.php
    ListMessageContractsCommand.php
    ValidateMessageCommand.php
    ValidateMessageExamplesCommand.php
  Testing/
    MessageAssert.php
resources/
  stubs/
    message-contract.stub
tests/
  Unit/
  Feature/
  Console/
  Fixtures/
```

## 6. Implementation Phases
1. **Phase 1 - Repository and Package Foundation:** Composer setup, service provider, config file, exception base class.
2. **Phase 2 - Core Contract Model:** `MessageContract` base class.
3. **Phase 3 - Message Serialization:** `Message` DTO, serialization and parsing logic.
4. **Phase 4 - Registry:** `MessageContractRegistry` to map names and versions to classes.
5. **Phase 5 - Validation:** `MessageValidator` leveraging Laravel's Validator for incoming/outgoing payloads.
6. **Phase 6 - Artisan Commands:** `make`, `list`, `validate`, `validate-examples`.
7. **Phase 7 - Testing Helpers:** `MessageAssert` utilities.
8. **Phase 8 - Documentation and Cleanup:** Update examples, PHPDocs, and naming.
9. **Phase 9 - Finalization:**
   - [x] Write `README.md` containing:
   - [x] Write `docs/` support files if necessary.

## 7. Test Plan
- Unit test for `MessageContract` methods.
- Unit tests for `Message` DTO serialization and parsing.
- Unit tests for `MessageContractRegistry` duplication/resolution.
- Unit tests for `MessageValidator` with valid/invalid data, strict mode, missing rules.
- Feature tests for producer (validation before publish) and consumer (validation on receive).
- Command tests for `make:message-contract`, `list`, etc. using Orchestra Testbench.
- Ensure 100% core test coverage. Run tests via Pest or PHPUnit depending on existing repo config.

## 8. Risks and Assumptions
- **Assumption:** The package depends heavily on `illuminate/support`, `illuminate/validation`, and `illuminate/console`. We'll set the minimal constraints (e.g. Laravel 10/11) in `composer.json`.
- **Assumption:** The repository currently might not have Pest configured. I will inspect the repo and use whichever is configured (or install Pest if it's completely empty).
- **Risk:** Handling `meta` fields elegantly without requiring them, preserving strict mode semantics.

## 9. Files/Classes to Create
- **Config:** `config/message-contracts.php`
- **Exceptions:** All specific package exceptions under `Satheez\MessageContracts\Exceptions`.
- **Core:** `MessageContract`, `Message`, `MessageValidationResult`, `MessageContractRegistry`, `MessageValidator`, `MessageSerializer`, `MessageParser`.
- **Commands:** `MakeMessageContractCommand`, `ListMessageContractsCommand`, `ValidateMessageCommand`, `ValidateMessageExamplesCommand`.
- **Testing:** `MessageAssert`.

## 10. Commands to Implement
- `php artisan make:message-contract Name`
- `php artisan message-contracts:list`
- `php artisan message-contracts:validate`
- `php artisan message-contracts:validate-examples`

## 11. Public API Proposal
```php
// Creating and sending
$message = UserRegisteredV1Message::message($payloadData);
$json = $message->toJson(); // Validates before serializing

// Consuming
$message = Message::fromJson($rawJson);
$message->validateOrFail(); // Looks up contract via registry and validates payload
$data = $message->payload();
```

## 12. Naming Migration Notes
- Replaced `laravel-payload-contracts` with `laravel-message-contracts`.
- Replaced `Satheez\PayloadContracts` namespace with `Satheez\MessageContracts`.
- Replaced `PayloadContract` with `MessageContract`.
- Replaced `PayloadMessage` with `Message`.
- Configuration file changed from `payload-contracts.php` to `message-contracts.php`.
- Artisan command namespaces updated to `message-contracts:*`.

## 13. Open Questions
> [!IMPORTANT]
> User Review Required:
> - Is Pest testing framework preferred to be installed from scratch if it isn't already set up?
> - Do you approve of this naming migration strategy before I start implementation?
