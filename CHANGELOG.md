# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
