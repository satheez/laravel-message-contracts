<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Tests\Fixtures\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class UserRegisteredV1Message extends MessageContract
{
    public static function contract(): string
    {
        return 'user.registered';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string'],
            'registered_at' => ['required', 'date'],
        ];
    }

    public static function example(): array
    {
        return [
            'user_id' => 123,
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'registered_at' => '2026-05-21T07:30:00Z',
        ];
    }

    public static function description(): string
    {
        return 'Published when a new user completes registration.';
    }
}
