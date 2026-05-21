<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\Registry\MessageContractRegistry;

final class TestOrderCreatedPayload extends MessageContract
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
        return ['id' => 'required|integer'];
    }
}

it('generates a snapshot and checks compatibility', function (): void {
    $registry = app(MessageContractRegistry::class);
    $registry->register(TestOrderCreatedPayload::class);

    config(['message-contracts.contracts' => [TestOrderCreatedPayload::class]]);

    $snapshotPath = storage_path('snapshot.json');
    if (File::exists($snapshotPath)) {
        File::delete($snapshotPath);
    }

    $this->artisan('message-contracts:snapshot', [
        '--output' => $snapshotPath,
    ])->assertSuccessful();

    expect(File::exists($snapshotPath))->toBeTrue();

    $this->artisan('message-contracts:check-breaking-changes', [
        '--against' => $snapshotPath,
    ])->assertSuccessful()
        ->expectsOutput('No breaking changes detected.');

    File::delete($snapshotPath);
});
