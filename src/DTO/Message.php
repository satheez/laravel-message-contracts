<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\DTO;

use JsonException;
use Satheez\MessageContracts\Exceptions\InvalidMessageException;
use Satheez\MessageContracts\Exceptions\MessageParsingException;
use Satheez\MessageContracts\Exceptions\MessageSerializationException;
use Satheez\MessageContracts\Exceptions\MessageValidationException;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Validation\MessageValidator;

/**
 * Transport-agnostic message DTO.
 *
 * Carries the contract name, version, business payload, and optional meta.
 * Produced by MessageContract::message() on the producer side and parsed from
 * raw JSON/array on the consumer side via fromJson() / fromArray().
 */
final readonly class Message
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        private string $contract,
        private int $version,
        private array $payload,
        private array $meta = [],
    ) {}

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function contract(): string
    {
        return $this->contract;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * Access the full payload array, or a single dot-notation key.
     */
    public function payload(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        return data_get($this->payload, $key, $default);
    }

    /**
     * Access the full meta array, or a single dot-notation key.
     */
    public function meta(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->meta;
        }

        return data_get($this->meta, $key, $default);
    }

    // ──────────────────────────────────────────────
    // Serialization
    // ──────────────────────────────────────────────

    /**
     * Serialize the message to an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $keys = config('message-contracts.message_keys', [
            'contract' => 'contract',
            'version' => 'version',
            'payload' => 'payload',
            'meta' => 'meta',
        ]);

        $data = [
            $keys['contract'] => $this->contract,
            $keys['version'] => $this->version,
            $keys['payload'] => $this->payload,
        ];

        if ($this->meta !== []) {
            $data[$keys['meta']] = $this->meta;
        }

        return $data;
    }

    /**
     * Serialize the message to a JSON string.
     *
     * @throws MessageSerializationException
     */
    public function toJson(bool $pretty = false): string
    {
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        try {
            return json_encode($this->toArray(), $flags);
        } catch (JsonException $e) {
            throw MessageSerializationException::encodingFailed(
                $this->contract,
                $this->version,
                $e->getMessage(),
            );
        }
    }

    // ──────────────────────────────────────────────
    // Parsing (consumer side)
    // ──────────────────────────────────────────────

    /**
     * Parse a JSON string into a Message DTO.
     * Does NOT validate the payload against a contract — call validateOrFail() next.
     *
     * @throws MessageParsingException
     * @throws InvalidMessageException
     */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw MessageParsingException::invalidJson($e->getMessage());
        }

        if (! is_array($data) || array_is_list($data)) {
            throw MessageParsingException::notAnObject();
        }

        return self::fromArray($data);
    }

    /**
     * Build a Message DTO from a raw associative array.
     * Does NOT validate the payload against a contract — call validateOrFail() next.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidMessageException
     */
    public static function fromArray(array $data): self
    {
        $keys = config('message-contracts.message_keys', [
            'contract' => 'contract',
            'version' => 'version',
            'payload' => 'payload',
            'meta' => 'meta',
        ]);

        $missing = [];

        foreach (['contract', 'version', 'payload'] as $required) {
            if (! array_key_exists($keys[$required], $data)) {
                $missing[] = $keys[$required];
            }
        }

        if ($missing !== []) {
            throw InvalidMessageException::missingKeys($missing);
        }

        return new self(
            contract: (string) $data[$keys['contract']],
            version: (int) $data[$keys['version']],
            payload: (array) $data[$keys['payload']],
            meta: isset($data[$keys['meta']]) ? (array) $data[$keys['meta']] : [],
        );
    }

    // ──────────────────────────────────────────────
    // Validation (consumer side)
    // ──────────────────────────────────────────────

    /**
     * Validate the payload against the registered contract.
     * Returns a result object without throwing.
     */
    public function validate(): MessageValidationResult
    {
        $contractClass = app(MessageContractRegistry::class)
            ->resolve($this->contract, $this->version);

        return app(MessageValidator::class)->validate($contractClass, $this->payload);
    }

    /**
     * Validate the payload against the registered contract.
     * Throws MessageValidationException on failure.
     *
     * @throws MessageValidationException
     */
    public function validateOrFail(): void
    {
        $contractClass = app(MessageContractRegistry::class)
            ->resolve($this->contract, $this->version);

        app(MessageValidator::class)->validateOrFail($contractClass, $this->payload);
    }
}
