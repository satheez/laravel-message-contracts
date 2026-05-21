<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Tests\Fixtures\MessageContracts;

use Satheez\MessageContracts\Contracts\MessageContract;

final class OrderCreatedV1Message extends MessageContract
{
    public static function contract(): string
    {
        return 'order.created';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'total_amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'created_at' => ['required', 'date'],
        ];
    }

    public static function example(): array
    {
        return [
            'order_id' => 1001,
            'user_id' => 123,
            'total_amount' => 149.99,
            'currency' => 'USD',
            'created_at' => '2026-05-21T07:30:00Z',
        ];
    }
}
