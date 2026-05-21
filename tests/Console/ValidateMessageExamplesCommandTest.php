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

it('all contract examples pass validation', function (): void {
    $this->artisan('message-contracts:validate-examples')
        ->expectsOutputToContain('passed')
        ->assertExitCode(0);
});

it('warns when no contracts are registered', function (): void {
    $this->app->singleton(MessageContractRegistry::class, fn (): MessageContractRegistry => new MessageContractRegistry);

    $this->artisan('message-contracts:validate-examples')
        ->expectsOutputToContain('No message contracts are registered')
        ->assertExitCode(0);
});
