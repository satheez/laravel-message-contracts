<?php

declare(strict_types=1);

use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\Exceptions\InvalidMessageException;
use Satheez\MessageContracts\Exceptions\MessageValidationException;
use Satheez\MessageContracts\Exceptions\UnknownMessageContractException;
use Satheez\MessageContracts\Exceptions\UnknownMessageContractVersionException;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

// Register our fixture contract in the test registry
beforeEach(function (): void {
    app(MessageContractRegistry::class)->register(UserRegisteredV1Message::class);
});

// ─── parsing ──────────────────────────────────────────────────────────────────

it('parses a valid incoming message', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => [
            'user_id' => 123,
            'email' => 'john@example.com',
            'registered_at' => '2026-05-21T07:30:00Z',
        ],
    ]);

    expect($message->contract())->toBe('user.registered')
        ->and($message->version())->toBe(1);
});

it('parses a valid incoming JSON message', function (): void {
    $raw = json_encode([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => ['user_id' => 5, 'email' => 'x@y.com', 'registered_at' => '2026-05-21T07:30:00Z'],
    ]);

    $message = Message::fromJson($raw);
    expect($message->payload('user_id'))->toBe(5);
});

// ─── consumer-side validation ─────────────────────────────────────────────────

it('validates a valid incoming message successfully', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => [
            'user_id' => 123,
            'email' => 'john@example.com',
            'registered_at' => '2026-05-21T07:30:00Z',
        ],
    ]);

    expect($message->validate()->passed())->toBeTrue();
});

it('throws when incoming payload is invalid', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => ['email' => 'not-valid'],
    ]);

    $message->validateOrFail();
})->throws(MessageValidationException::class);

it('throws InvalidMessageException when contract key is missing', function (): void {
    Message::fromArray(['version' => 1, 'payload' => []]);
})->throws(InvalidMessageException::class);

it('throws InvalidMessageException when version key is missing', function (): void {
    Message::fromArray(['contract' => 'user.registered', 'payload' => []]);
})->throws(InvalidMessageException::class);

it('throws InvalidMessageException when payload key is missing', function (): void {
    Message::fromArray(['contract' => 'user.registered', 'version' => 1]);
})->throws(InvalidMessageException::class);

it('throws UnknownMessageContractException for unregistered contract', function (): void {
    $message = Message::fromArray([
        'contract' => 'unknown.event',
        'version' => 1,
        'payload' => [],
    ]);

    $message->validateOrFail();
})->throws(UnknownMessageContractException::class);

it('throws UnknownMessageContractVersionException for unregistered version', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 99,
        'payload' => [],
    ]);

    $message->validateOrFail();
})->throws(UnknownMessageContractVersionException::class);
