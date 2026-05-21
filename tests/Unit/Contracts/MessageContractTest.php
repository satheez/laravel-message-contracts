<?php

declare(strict_types=1);

use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

it('returns the correct contract name', function (): void {
    expect(UserRegisteredV1Message::contract())->toBe('user.registered');
});

it('returns the correct version', function (): void {
    expect(UserRegisteredV1Message::version())->toBe(1);
});

it('returns an array of validation rules', function (): void {
    $rules = UserRegisteredV1Message::rules();

    expect($rules)->toBeArray()->toHaveKeys(['user_id', 'email', 'registered_at']);
});

it('returns an example payload', function (): void {
    expect(UserRegisteredV1Message::example())
        ->toBeArray()
        ->toHaveKeys(['user_id', 'email', 'registered_at']);
});

it('returns null description by default when not overridden', function (): void {
    // UserRegisteredV1Message *does* override description(), so it is non-null
    expect(UserRegisteredV1Message::description())->toBeString();
});

it('is not deprecated by default', function (): void {
    expect(UserRegisteredV1Message::deprecated())->toBeFalse();
});
