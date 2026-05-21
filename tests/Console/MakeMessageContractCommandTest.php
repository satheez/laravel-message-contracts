<?php

declare(strict_types=1);

it('generates a V1 message contract class', function (): void {
    $this->artisan('make:message-contract', ['name' => 'UserRegistered'])
        ->assertSuccessful();
});

it('generates a versioned message contract class', function (): void {
    $this->artisan('make:message-contract', ['name' => 'OrderCreated', '--contract-version' => 2])
        ->assertSuccessful();
});
