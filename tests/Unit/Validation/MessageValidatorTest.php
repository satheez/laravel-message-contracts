<?php

declare(strict_types=1);

use Satheez\MessageContracts\Exceptions\MessageValidationException;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;
use Satheez\MessageContracts\Validation\MessageValidator;

function messageValidator(): MessageValidator
{
    return app(MessageValidator::class);
}

// ─── passing ──────────────────────────────────────────────────────────────────

it('passes a valid payload', function (): void {
    $result = messageValidator()->validate(UserRegisteredV1Message::class, [
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($result->passed())->toBeTrue()->and($result->errors())->toBeEmpty();
});

it('allows nullable field to be null', function (): void {
    $result = messageValidator()->validate(UserRegisteredV1Message::class, [
        'user_id' => 123,
        'email' => 'john@example.com',
        'name' => null,
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($result->passed())->toBeTrue();
});

// ─── failing ──────────────────────────────────────────────────────────────────

it('fails when required fields are missing', function (): void {
    $result = messageValidator()->validate(UserRegisteredV1Message::class, []);

    expect($result->failed())->toBeTrue()
        ->and($result->errors())->toHaveKeys(['user_id', 'email', 'registered_at']);
});

it('fails when email is invalid', function (): void {
    $result = messageValidator()->validate(UserRegisteredV1Message::class, [
        'user_id' => 123,
        'email' => 'not-an-email',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($result->failed())->toBeTrue()
        ->and($result->errors())->toHaveKey('email');
});

it('fails when user_id is a string, not integer', function (): void {
    $result = messageValidator()->validate(UserRegisteredV1Message::class, [
        'user_id' => 'abc',
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    expect($result->failed())->toBeTrue()
        ->and($result->errors())->toHaveKey('user_id');
});

// ─── strict mode ──────────────────────────────────────────────────────────────

it('rejects unknown fields in strict mode', function (): void {
    config()->set('message-contracts.strict', true);

    $result = messageValidator()->validate(UserRegisteredV1Message::class, [
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
        'unexpected_field' => 'value',
    ]);

    expect($result->failed())->toBeTrue()
        ->and($result->errors())->toHaveKey('unexpected_field');
});

it('allows unknown fields when strict mode is disabled', function (): void {
    config()->set('message-contracts.strict', false);

    $result = messageValidator()->validate(UserRegisteredV1Message::class, [
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
        'unexpected_field' => 'value',
    ]);

    expect($result->passed())->toBeTrue();
});

// ─── validateOrFail ───────────────────────────────────────────────────────────

it('throws MessageValidationException when validateOrFail fails', function (): void {
    messageValidator()->validateOrFail(UserRegisteredV1Message::class, ['email' => 'bad']);
})->throws(MessageValidationException::class);

it('exception carries structured error data', function (): void {
    try {
        messageValidator()->validateOrFail(UserRegisteredV1Message::class, ['email' => 'bad']);
    } catch (MessageValidationException $e) {
        expect($e->getContract())->toBe('user.registered')
            ->and($e->getVersion())->toBe(1)
            ->and($e->getErrors())->toHaveKey('email');
    }
});
