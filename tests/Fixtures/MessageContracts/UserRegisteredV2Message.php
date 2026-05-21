<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Tests\Fixtures\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class UserRegisteredV2Message extends MessageContract
{
    public static function contract(): string
    {
        return 'user.registered';
    }

    public static function version(): int
    {
        return 2;
    }

    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],   // became required in v2
            'registered_at' => ['required', 'date'],
            'locale' => ['required', 'string', 'size:2'],
        ];
    }

    public static function example(): array
    {
        return [
            'user_id' => 456,
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'registered_at' => '2026-05-21T07:30:00Z',
            'locale' => 'en',
        ];
    }
}
