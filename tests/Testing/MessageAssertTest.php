<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Testing\MessageAssert;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

beforeEach(function (): void {
    app(MessageContractRegistry::class)->register(UserRegisteredV1Message::class);
});

// ─── assertValid ──────────────────────────────────────────────────────────────

it('assertValid passes for a valid payload', function (): void {
    MessageAssert::assertValid(UserRegisteredV1Message::class, [
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);
});

it('assertValid fails (PHPUnit assert) for an invalid payload', function (): void {
    expect(fn () => MessageAssert::assertValid(UserRegisteredV1Message::class, ['email' => 'bad']))
        ->toThrow(AssertionFailedError::class);
});

// ─── assertInvalid ────────────────────────────────────────────────────────────

it('assertInvalid passes for an invalid payload', function (): void {
    MessageAssert::assertInvalid(UserRegisteredV1Message::class, ['email' => 'bad']);
});

it('assertInvalidFields reports the expected failing fields', function (): void {
    MessageAssert::assertInvalidFields(UserRegisteredV1Message::class, [], ['user_id', 'email', 'registered_at']);
});

// ─── assertMessageMatchesContract ─────────────────────────────────────────────

it('assertMessageMatchesContract passes for a matching message array', function (): void {
    $messageArray = UserRegisteredV1Message::message([
        'user_id' => 1,
        'email' => 'a@b.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ])->toArray();

    MessageAssert::assertMessageMatchesContract(UserRegisteredV1Message::class, $messageArray);
});

// ─── assertContractRegistered / assertContractNotRegistered ───────────────────

it('assertContractRegistered passes when contract is registered', function (): void {
    MessageAssert::assertContractRegistered('user.registered', 1);
});

it('assertContractNotRegistered passes when contract is not registered', function (): void {
    MessageAssert::assertContractNotRegistered('unknown.event', 99);
});
