# Development Status: Laravel Payload Contracts

This document tracks the implementation progress of the package features according to the original product roadmap.

---

## ✅ Completed Phases

### Phase 1: Core Package Foundation
- **Package skeleton**: Composer configuration and Service Provider implemented.
- **Base `MessageContract` class**: Defined the abstract base class for payload contracts.
- **`Message` DTO**: Standardized wrapper for message contracts (containing `contract`, `version`, `payload`, `meta`).
- **Serialization**: Basic array and JSON serialization of `Message` DTO.

### Phase 2: Payload Validation
- **Message Validator Service**: Validates both outgoing and incoming payloads against contract rules.
- **Strict Mode**: Optionally rejects payloads containing unknown keys not defined in the contract's rules.

### Phase 3: Contract Registry
- **`MessageContractRegistry`**: Config-based registry resolving contracts by `name` and `version`.
- **Duplicate/Missing Checks**: Detection logic for conflicting contract definitions.

### Phase 4: Artisan Developer Experience
- `php artisan make:payload-contract`: Scaffolds a new contract class.
- `php artisan payload-contracts:list`: Lists all registered contracts.
- `php artisan payload-contracts:validate`: Validates an incoming JSON string payload.
- `php artisan payload-contracts:validate-examples`: Verifies the `example()` payload output against its own rules.

### Phase 5: Testing Helpers
- **`MessageAssert`**: PHPUnit/Pest testing helpers to assert payloads are valid or invalid against specific contracts.

### Phase 6: JSON Schema Export
- **`LaravelRuleMapper`**: Recursively maps Laravel validation rules into Draft 2020-12 JSON Schema definitions.
- **`JsonSchemaGenerator`**: Wraps the schema and manages `payload-only` vs `full message` structures.
- **`message-contracts:export-json-schema` command**: Automated JSON schema file generation for documentation and non-Laravel consumption.

### Phase 7: Compatibility Checks
- **`SnapshotManager`**: Creates and saves JSON snapshots of registered schemas.
- **`SchemaComparator`**: Detects safe additions, warnings, and breaking removals/modifications between snapshots.
- **`message-contracts:snapshot` command**: Generates a baseline snapshot file.
- **`message-contracts:check-breaking-changes` command**: Compares current schema state against a baseline.

---

## ⏳ Pending Phases

### Phase 8: AsyncAPI Export
**Objective**: Generate standardized AsyncAPI YAML documentation.
- **Pending Tasks**:
  - Add optional AsyncAPI metadata to `MessageContract` (`channel()`, `description()`, `direction()`, etc.).
  - Implement `AsyncApiGenerator` to construct the channels, operations, and components object tree.
  - Create the `php artisan payload-contracts:export-asyncapi` command.

### Phase 9: Spatie Laravel Data Integration
**Objective**: Allow using `spatie/laravel-data` DTOs directly as contract payload definitions.
- **Pending Tasks**:
  - Create an optional `DataPayloadContract` base class.
  - Delegate `rules()` and validation to the `Data` object when the contract extends `DataPayloadContract`.

### Phase 10: Stable v1 Release
**Objective**: Final polish, documentation, and CI setup.
- **Pending Tasks**:
  - Add GitHub Actions CI workflow (testing across Laravel/PHP versions).
  - Finalize README and usage guides.
  - Set up code style tools (Pint/PHPStan) for maintaining code quality.
