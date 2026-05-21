<?php

declare(strict_types=1);

use Satheez\MessageContracts\Exceptions\DuplicateMessageContractException;
use Satheez\MessageContracts\Exceptions\InvalidMessageContractException;
use Satheez\MessageContracts\Exceptions\UnknownMessageContractException;
use Satheez\MessageContracts\Exceptions\UnknownMessageContractVersionException;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\OrderCreatedV1Message;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV2Message;

function freshRegistry(): MessageContractRegistry
{
    return new MessageContractRegistry;
}

it('registers a contract and confirms it exists', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);

    expect($registry->has('user.registered', 1))->toBeTrue();
});

it('resolves a contract by name and version', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);

    expect($registry->resolve('user.registered', 1))->toBe(UserRegisteredV1Message::class);
});

it('supports multiple versions of the same contract', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);
    $registry->register(UserRegisteredV2Message::class);

    expect($registry->resolve('user.registered', 1))->toBe(UserRegisteredV1Message::class)
        ->and($registry->resolve('user.registered', 2))->toBe(UserRegisteredV2Message::class);
});

it('supports multiple distinct contracts', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);
    $registry->register(OrderCreatedV1Message::class);

    expect($registry->all())->toHaveCount(2);
});

it('lists all registered contracts', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);
    $registry->register(OrderCreatedV1Message::class);

    $all = $registry->all();
    expect($all)->toContain(UserRegisteredV1Message::class)
        ->and($all)->toContain(OrderCreatedV1Message::class);
});

it('returns versions for a given contract name', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);
    $registry->register(UserRegisteredV2Message::class);

    expect($registry->versionsFor('user.registered'))->toBe([1, 2]);
});

it('throws DuplicateMessageContractException on duplicate registration', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);
    $registry->register(UserRegisteredV1Message::class);
})->throws(DuplicateMessageContractException::class);

it('throws UnknownMessageContractException for unknown contract', function (): void {
    $registry = freshRegistry();
    $registry->resolve('unknown.event', 1);
})->throws(UnknownMessageContractException::class);

it('throws UnknownMessageContractVersionException for known contract but wrong version', function (): void {
    $registry = freshRegistry();
    $registry->register(UserRegisteredV1Message::class);
    $registry->resolve('user.registered', 99);
})->throws(UnknownMessageContractVersionException::class);

it('throws InvalidMessageContractException for non-contract class', function (): void {
    $registry = freshRegistry();
    $registry->register(stdClass::class); // @phpstan-ignore-line
})->throws(InvalidMessageContractException::class);
