<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\DTO;

/**
 * Represents the outcome of validating a payload against a message contract.
 *
 * Use validate() for a non-throwing result; use validateOrFail() to throw.
 */
final readonly class MessageValidationResult
{
    /**
     * @param  array<string, string[]>  $errors  Laravel-style per-field error arrays.
     */
    public function __construct(
        private bool $passed,
        private array $errors = [],
    ) {}

    public static function pass(): self
    {
        return new self(true);
    }

    /** @param array<string, string[]> $errors */
    public static function fail(array $errors): self
    {
        return new self(false, $errors);
    }

    public function passed(): bool
    {
        return $this->passed;
    }

    public function failed(): bool
    {
        return ! $this->passed;
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return string[] Flat list of all error messages across all fields. */
    public function allErrors(): array
    {
        return array_merge(...array_values($this->errors));
    }
}
