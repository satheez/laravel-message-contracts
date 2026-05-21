<?php

declare(strict_types=1);

use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Tests\Fixtures\MessageContracts\UserRegisteredV1Message;

beforeEach(function (): void {
    app(MessageContractRegistry::class)->register(UserRegisteredV1Message::class);
});

it('validates a valid payload JSON against a registered contract', function (): void {
    $json = json_encode([
        'user_id' => 123,
        'email' => 'john@example.com',
        'registered_at' => '2026-05-21T07:30:00Z',
    ]);

    $this->artisan('message-contracts:validate', [
        'contract' => 'user.registered',
        '--contract-version' => 1,
        '--json' => $json,
    ])
        ->expectsOutputToContain('Payload is valid')
        ->assertExitCode(0);
});

it('fails validation for an invalid payload', function (): void {
    $json = json_encode(['email' => 'bad']);

    $this->artisan('message-contracts:validate', [
        'contract' => 'user.registered',
        '--contract-version' => 1,
        '--json' => $json,
    ])->assertExitCode(1);
});

it('validates a full message envelope with --message flag', function (): void {
    $json = json_encode([
        'contract' => 'user.registered',
        'version' => 1,
        'payload' => [
            'user_id' => 123,
            'email' => 'john@example.com',
            'registered_at' => '2026-05-21T07:30:00Z',
        ],
    ]);

    $this->artisan('message-contracts:validate', [
        'contract' => 'user.registered',
        '--contract-version' => 1,
        '--json' => $json,
        '--message' => true,
    ])
        ->expectsOutputToContain('Payload is valid')
        ->assertExitCode(0);
});
