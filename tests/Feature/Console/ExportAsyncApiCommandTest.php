<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Satheez\MessageContracts\Registry\MessageContractRegistry;

it('exports asyncapi file in json format', function (): void {
    $registry = app(MessageContractRegistry::class);
    $registry->register(TestUserRegisteredAsyncApiPayload::class);

    config(['message-contracts.contracts' => [TestUserRegisteredAsyncApiPayload::class]]);

    $outputPath = storage_path('asyncapi.json');
    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }

    $this->artisan('message-contracts:export-asyncapi', [
        '--output' => $outputPath,
        '--format' => 'json',
    ])->assertSuccessful();

    expect(File::exists($outputPath))->toBeTrue();

    $content = json_decode(File::get($outputPath), true);

    expect($content['asyncapi'])->toBe('2.6.0')
        ->and($content['channels']['users.events']['subscribe']['message']['$ref'])->toBe('#/components/messages/user.registered.v1.message');

    File::delete($outputPath);
});
