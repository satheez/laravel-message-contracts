# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Dropped support for Laravel 10 and 11. The package now requires Laravel 12 or 13 (`laravel/framework: ^12.0|^13.0`).
- PHP `^8.2` remains the package floor. Laravel 13 still requires PHP 8.3+.
- Development dependencies now follow that floor: Orchestra Testbench `^10.0|^11.0`, Pest 3 or 4, and Larastan 3 with PHPStan 2.
- CI covers Laravel 12 on PHP 8.2–8.5 and Laravel 13 on PHP 8.3–8.5. The redundant `run-tests` workflow was removed.

## [1.0.0] - 2026-05-22

### Added

- `MessageContract` base class for defining named, versioned payload contracts.
- `Message` DTO for transport-agnostic message serialization and parsing.
- `MessageContractRegistry` for resolving contracts by name and version.
- `MessageValidator` with strict mode and Laravel validation rules.
- `JsonSchemaGenerator` for exporting payload and envelope JSON Schemas.
- `AsyncApiGenerator` for generating AsyncAPI 2.6.0 documentation.
- `SnapshotManager` and `SchemaComparator` for compatibility checks.
- `MessageAssert` testing helper for Pest and PHPUnit.
- `DataPayloadContract` for Spatie Laravel Data integration.
- Artisan commands: `make:message-contract`, `message-contracts:list`,
  `message-contracts:validate`, `message-contracts:validate-examples`,
  `message-contracts:export-json-schema`, `message-contracts:export-asyncapi`,
  `message-contracts:snapshot`, `message-contracts:check-breaking-changes`.
- Configurable envelope keys, metadata strategies (ULID/UUID), and strict mode.
- GitHub Actions CI for PHP 8.2–8.4 and Laravel 10–13.
