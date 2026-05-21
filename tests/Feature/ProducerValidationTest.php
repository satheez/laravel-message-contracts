<?php

declare(strict_types=1);

use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\Exceptions\MessageValidationException;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

// ─── producer side ────────────────────────────────────────────────────────────

it('creates a valid outgoing message from a contract', function (): void {
    $message = UserRegisteredV1Message::message([
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->contract())->toBe('user.registered')
        ->and($message->version())->toBe(1)
        ->and($message->payload('user_id'))->toBe(123);
});

it('throws MessageValidationException when outgoing payload is invalid', function (): void {
    UserRegisteredV1Message::message(['email' => 'not-valid']);
})->throws(MessageValidationException::class);

it('does not send when producer payload is invalid', function (): void {
    $sent = false;

    try {
        $message = UserRegisteredV1Message::message(['email' => 'bad']);
        // Simulate dispatch — this line must not be reached
        $sent = true;
    } catch (MessageValidationException) {
        // expected
    }

    expect($sent)->toBeFalse();
});

it('serializes outgoing message to JSON correctly', function (): void {
    $json = UserRegisteredV1Message::message([
        'user_id' => 99,
        'email' => 'a@b.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ])->toJson();

    $decoded = json_decode($json, true);

    expect($decoded)->toMatchArray([
        'contract' => 'user.registered',
        'version' => 1,
    ])->and($decoded['payload']['user_id'])->toBe(99);
});
