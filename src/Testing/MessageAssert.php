<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Testing;

use PHPUnit\Framework\Assert;
use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Validation\MessageValidator;

/**
 * Static assertion helpers for testing message contracts.
 *
 * Usage in PHPUnit / Pest:
 *
 *   MessageAssert::assertValid(UserRegisteredV1Message::class, $payload);
 *   MessageAssert::assertInvalid(UserRegisteredV1Message::class, $payload);
 *   MessageAssert::assertMessageMatchesContract(UserRegisteredV1Message::class, $messageArray);
 *   MessageAssert::assertContractRegistered('user.registered', 1);
 *   MessageAssert::assertContractNotRegistered('unknown.event', 1);
 */
final class MessageAssert
{
    // ──────────────────────────────────────────────
    // Payload assertions
    // ──────────────────────────────────────────────

    /**
     * Assert that the given payload is valid according to the contract's rules.
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $payload
     */
    public static function assertValid(string $contractClass, array $payload): void
    {
        $result = app(MessageValidator::class)->validate($contractClass, $payload);

        Assert::assertTrue(
            $result->passed(),
            sprintf(
                "Failed asserting that the payload is valid for [%s].\n\nErrors:\n%s",
                $contractClass,
                self::formatErrors($result->errors()),
            ),
        );
    }

    /**
     * Assert that the given payload is invalid according to the contract's rules.
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $payload
     */
    public static function assertInvalid(string $contractClass, array $payload): void
    {
        $result = app(MessageValidator::class)->validate($contractClass, $payload);

        Assert::assertTrue(
            $result->failed(),
            sprintf(
                'Failed asserting that the payload is invalid for [%s]. The payload passed validation unexpectedly.',
                $contractClass,
            ),
        );
    }

    /**
     * Assert that specific fields fail validation for the given payload.
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $payload
     * @param  string[]  $fields
     */
    public static function assertInvalidFields(string $contractClass, array $payload, array $fields): void
    {
        $result = app(MessageValidator::class)->validate($contractClass, $payload);

        Assert::assertTrue($result->failed(), 'Expected validation to fail but it passed.');

        foreach ($fields as $field) {
            Assert::assertArrayHasKey(
                $field,
                $result->errors(),
                "Expected field [{$field}] to have validation errors, but it did not.",
            );
        }
    }

    // ──────────────────────────────────────────────
    // Message structure assertions
    // ──────────────────────────────────────────────

    /**
     * Assert that the array representation of a message matches the given contract.
     *
     * @param  class-string<MessageContract>  $contractClass
     * @param  array<string, mixed>  $messageArray
     */
    public static function assertMessageMatchesContract(string $contractClass, array $messageArray): void
    {
        Assert::assertArrayHasKey('contract', $messageArray, 'Message array is missing the [contract] key.');
        Assert::assertArrayHasKey('version', $messageArray, 'Message array is missing the [version] key.');
        Assert::assertArrayHasKey('payload', $messageArray, 'Message array is missing the [payload] key.');

        Assert::assertSame(
            $contractClass::contract(),
            $messageArray['contract'],
            "Expected contract name [{$contractClass::contract()}] but got [{$messageArray['contract']}].",
        );

        Assert::assertSame(
            $contractClass::version(),
            $messageArray['version'],
            "Expected version [{$contractClass::version()}] but got [{$messageArray['version']}].",
        );

        self::assertValid($contractClass, $messageArray['payload']);
    }

    // ──────────────────────────────────────────────
    // Registry assertions
    // ──────────────────────────────────────────────

    /**
     * Assert that a contract/version pair is registered in the registry.
     */
    public static function assertContractRegistered(string $contract, int $version): void
    {
        $registry = app(MessageContractRegistry::class);

        Assert::assertTrue(
            $registry->has($contract, $version),
            "Failed asserting that contract [{$contract}] version [{$version}] is registered.",
        );
    }

    /**
     * Assert that a contract/version pair is NOT registered in the registry.
     */
    public static function assertContractNotRegistered(string $contract, int $version): void
    {
        $registry = app(MessageContractRegistry::class);

        Assert::assertFalse(
            $registry->has($contract, $version),
            "Failed asserting that contract [{$contract}] version [{$version}] is not registered.",
        );
    }

    // ──────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────

    /** @param array<string, string[]> $errors */
    private static function formatErrors(array $errors): string
    {
        $lines = [];
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $lines[] = "  - {$field}: {$message}";
            }
        }

        return implode("\n", $lines);
    }
}
