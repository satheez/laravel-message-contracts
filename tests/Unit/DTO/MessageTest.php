<?php

declare(strict_types=1);

use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\Exceptions\InvalidMessageException;
use Satheez\MessageContracts\Exceptions\MessageParsingException;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

// ─── fromArray ────────────────────────────────────────────────────────────────

it('builds a message from a valid array', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => ['user_id' => 123, 'email' => 'john@example.com', 'registered_at' => '2026-05-21T07:30:00Z'],
    ]);

    expect($message->contract())->toBe('user.registered')
        ->and($message->version())->toBe(1)
        ->and($message->payload('user_id'))->toBe(123);
});

it('throws InvalidMessageException when required keys are missing', function (): void {
    Message::fromArray(['payload' => []]);
})->throws(InvalidMessageException::class);

it('throws InvalidMessageException when contract key is missing', function (): void {
    Message::fromArray(['version' => 1, 'payload' => []]);
})->throws(InvalidMessageException::class);

// ─── fromJson ─────────────────────────────────────────────────────────────────

it('builds a message from valid JSON', function (): void {
    $json = '{"contract":"user.registered","version":1,"payload":{"user_id":123,"email":"a@b.com","registered_at":"2026-05-21T07:30:00Z"}}';
    $msg = Message::fromJson($json);

    expect($msg->contract())->toBe('user.registered')->and($msg->version())->toBe(1);
});

it('throws MessageParsingException for invalid JSON', function (): void {
    Message::fromJson('{invalid-json');
})->throws(MessageParsingException::class);

it('throws MessageParsingException for a JSON array (list)', function (): void {
    Message::fromJson('[1,2,3]');
})->throws(MessageParsingException::class);

// ─── toArray / toJson ─────────────────────────────────────────────────────────

it('serializes to array with required keys', function (): void {
    $message = UserRegisteredV1Message::message([
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($message->toArray())
        ->toHaveKey('contract', 'user.registered')
        ->toHaveKey('version', 1)
        ->toHaveKey('payload');
});

it('serializes to valid JSON', function (): void {
    $message = UserRegisteredV1Message::message([
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($message->toJson())->toBeJson();
});

// ─── payload() / meta() accessors ─────────────────────────────────────────────

it('returns full payload array when no key given', function (): void {
    $message = UserRegisteredV1Message::message([
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($message->payload())->toBeArray()->toHaveKey('user_id', 123);
});

it('returns a nested dot-notation payload key', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => ['address' => ['city' => 'Colombo']],
    ]);

    expect($message->payload('address.city'))->toBe('Colombo');
});

it('returns default when payload key does not exist', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => [],
    ]);

    expect($message->payload('missing', 'default'))->toBe('default');
});

it('returns meta values', function (): void {
    $message = Message::fromArray([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => [],
        'meta' => ['message_id' => 'abc123'],
    ]);

    expect($message->meta('message_id'))->toBe('abc123')
        ->and($message->meta())->toHaveKey('message_id');
});

// ─── round-trip ───────────────────────────────────────────────────────────────

it('survives an array round-trip', function (): void {
    $original = UserRegisteredV1Message::message([
        'user_id' => 42,
        'email' => 'test@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    $restored = Message::fromArray($original->toArray());

    expect($restored->contract())->toBe($original->contract())
        ->and($restored->version())->toBe($original->version())
        ->and($restored->payload())->toBe($original->payload());
});

it('survives a JSON round-trip', function (): void {
    $original = UserRegisteredV1Message::message([
        'user_id' => 42,
        'email' => 'test@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    $restored = Message::fromJson($original->toJson());

    expect($restored->toArray())->toBe($original->toArray());
});
