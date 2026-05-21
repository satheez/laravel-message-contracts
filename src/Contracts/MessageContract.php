<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Contracts;

use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\DTO\MessageValidationResult;
use Satheez\MessageContracts\Exceptions\MessageValidationException;
use Satheez\MessageContracts\Validation\MessageValidator;

/**
 * Base class for all message contracts.
 *
 * Extend this class to define a versioned, validated message contract:
 *
 *   final class UserRegisteredV1Message extends MessageContract
 *   {
 *       public static function contract(): string  { return 'user.registered'; }
 *       public static function version(): int      { return 1; }
 *       public static function rules(): array      { return [...]; }
 *   }
 */
abstract class MessageContract
{
    /**
     * The stable, dot-notation business name for this contract.
     * Must not include the version number.
     *
     * Example: 'user.registered'
     */
    abstract public static function contract(): string;

    /**
     * The integer version of this contract, starting at 1.
     * Increment only when introducing a breaking change.
     */
    abstract public static function version(): int;

    /**
     * Laravel validation rules applied to the payload data.
     *
     * @return array<string, string|string[]>
     */
    abstract public static function rules(): array;

    // ──────────────────────────────────────────────
    // Optional overrideable methods
    // ──────────────────────────────────────────────

    /**
     * A human-readable description of this contract's purpose.
     */
    public static function description(): ?string
    {
        return null;
    }

    /**
     * An example payload that is valid according to this contract's rules.
     * Used by `message-contracts:validate-examples` and documentation generators.
     *
     * @return array<string, mixed>
     */
    public static function example(): array
    {
        return [];
    }

    /**
     * Additional non-runtime metadata about the contract (owner, domain, etc.).
     *
     * @return array<string, mixed>
     */
    public static function metadata(): array
    {
        return [];
    }

    /**
     * Whether this contract version is deprecated.
     * Deprecated contracts remain functional but are flagged in `message-contracts:list`.
     */
    public static function deprecated(): bool
    {
        return false;
    }

    /**
     * Custom JSON Schema override for this payload.
     * If provided, this schema will be used instead of auto-generating from rules.
     *
     * @return array<string, mixed>|null
     */
    public static function schema(): ?array
    {
        return null;
    }

    // ──────────────────────────────────────────────
    // AsyncAPI Metadata (Optional)
    // ──────────────────────────────────────────────

    /**
     * The human-readable title for this contract (AsyncAPI).
     */
    public static function title(): ?string
    {
        return null;
    }

    /**
     * The channel (topic/queue) this message is typically published/subscribed to.
     */
    public static function channel(): ?string
    {
        return null;
    }

    /**
     * Direction of the message relative to the application.
     * Expected values: 'publish' (we send it), 'subscribe' (we receive it), or 'both'.
     */
    public static function direction(): string
    {
        return 'both';
    }

    /**
     * Tags to organize the contract in AsyncAPI documentation.
     *
     * @return string[]
     */
    public static function tags(): array
    {
        return [];
    }

    // ──────────────────────────────────────────────
    // Factory & validation helpers
    // ──────────────────────────────────────────────

    /**
     * Create a validated Message DTO from the given payload data.
     *
     * Validates the payload against this contract's rules() before building
     * the message. Throws MessageValidationException on failure.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $meta
     *
     * @throws MessageValidationException
     */
    public static function message(array $payload, array $meta = []): Message
    {
        return app(MessageValidator::class)->createMessage(static::class, $payload, $meta);
    }

    /**
     * Validate a raw payload array against this contract's rules without
     * creating a message. Returns a result object (never throws).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function validate(array $payload): MessageValidationResult
    {
        return app(MessageValidator::class)->validate(static::class, $payload);
    }

    /**
     * Validate a raw payload array, throwing on failure.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws MessageValidationException
     */
    public static function validateOrFail(array $payload): void
    {
        app(MessageValidator::class)->validateOrFail(static::class, $payload);
    }
}
