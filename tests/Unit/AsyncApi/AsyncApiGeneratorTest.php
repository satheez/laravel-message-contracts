<?php

declare(strict_types=1);

use Satheez\MessageContracts\AsyncApi\AsyncApiGenerator;
use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\JsonSchema\JsonSchemaGenerator;
use Satheez\MessageContracts\JsonSchema\LaravelRuleMapper;

final class TestUserRegisteredAsyncApiPayload extends MessageContract
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
        return ['id' => 'required|integer'];
    }

    public static function channel(): string
    {
        return 'users.events';
    }

    public static function direction(): string
    {
        return 'publish';
    }
}

final class TestUserUpdatedAsyncApiPayload extends MessageContract
{
    public static function contract(): string
    {
        return 'user.updated';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function rules(): array
    {
        return ['id' => 'required|integer'];
    }

    public static function direction(): string
    {
        return 'subscribe';
    }
}

it('generates asyncapi structure correctly', function (): void {
    $generator = new AsyncApiGenerator(new JsonSchemaGenerator(new LaravelRuleMapper));

    $result = $generator->generate([
        TestUserRegisteredAsyncApiPayload::class,
        TestUserUpdatedAsyncApiPayload::class,
    ]);

    // Check base structure
    expect($result['asyncapi'])->toBe('2.6.0')
        ->and($result['info']['version'])->toBe('1.0.0');

    // Check channels
    expect($result['channels'])->toHaveKeys(['users.events', 'user.updated']);

    // Check operations (since users.events is 'publish' by application, consumer should 'subscribe')
    expect($result['channels']['users.events']['subscribe']['message']['$ref'])->toBe('#/components/messages/user.registered.v1.message');

    // Check operations (since user.updated is 'subscribe' by application, consumer should 'publish')
    expect($result['channels']['user.updated']['publish']['message']['$ref'])->toBe('#/components/messages/user.updated.v1.message');

    // Check components
    expect($result['components']['messages'])->toHaveKeys([
        'user.registered.v1.message',
        'user.updated.v1.message',
    ]);

    expect($result['components']['schemas'])->toHaveKeys([
        'user.registered.v1',
        'user.updated.v1',
    ]);
});
