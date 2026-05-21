<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\Registry\MessageContractRegistry;

final class TestUserRegisteredPayload extends MessageContract
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
}

it('exports json schema files', function (): void {
    $registry = app(MessageContractRegistry::class);
    $registry->register(TestUserRegisteredPayload::class);

    config(['message-contracts.contracts' => [TestUserRegisteredPayload::class]]);

    $outputDir = storage_path('schemas');
    if (! File::exists($outputDir)) {
        File::makeDirectory($outputDir, 0755, true);
    }

    $this->artisan('message-contracts:export-json-schema', [
        '--output' => $outputDir,
    ])->assertSuccessful();

    $expectedFile = $outputDir.'/user.registered.v1.schema.json';

    expect(File::exists($expectedFile))->toBeTrue();

    $content = json_decode(File::get($expectedFile), true);

    expect($content['$id'])->toBe('user.registered.v1')
        ->and($content['properties']['id']['type'])->toBe('integer');

    File::deleteDirectory($outputDir);
});
