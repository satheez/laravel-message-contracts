<?php

declare(strict_types=1);

use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\OrderCreatedV1Message;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

beforeEach(function (): void {
    $registry = app(MessageContractRegistry::class);
    $registry->register(UserRegisteredV1Message::class);
    $registry->register(OrderCreatedV1Message::class);
});

it('lists registered contracts in table format', function (): void {
    $this->artisan('message-contracts:list')
        ->expectsOutputToContain('user.registered')
        ->expectsOutputToContain('order.created')
        ->assertExitCode(0);
});

it('outputs JSON format when --format=json', function (): void {
    $this->artisan('message-contracts:list', ['--format' => 'json'])
        ->expectsOutputToContain('"contract"')
        ->assertExitCode(0);
});

it('warns when no contracts are registered', function (): void {
    // Override with empty config
    config()->set('message-contracts.contracts', []);

    // Fresh registry without any contracts
    $this->app->singleton(MessageContractRegistry::class, fn (): MessageContractRegistry => new MessageContractRegistry);

    $this->artisan('message-contracts:list')
        ->expectsOutputToContain('No message contracts are registered')
        ->assertExitCode(0);
});
