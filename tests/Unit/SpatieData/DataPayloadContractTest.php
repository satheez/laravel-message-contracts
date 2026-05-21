<?php

declare(strict_types=1);

use Satheez\MessageContracts\SpatieData\DataPayloadContract;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\LaravelDataServiceProvider;

final class TestUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

final class TestUserDataContract extends DataPayloadContract
{
    public static function contract(): string
    {
        return 'user.data';
    }

    public static function version(): int
    {
        return 1;
    }

    public static function dataClass(): string
    {
        return TestUserData::class;
    }
}

beforeEach(function (): void {
    $this->app->register(LaravelDataServiceProvider::class);
});

it('returns rules from spatie data class', function (): void {
    $rules = TestUserDataContract::rules();

    expect($rules)->toHaveKeys(['id', 'name'])
        ->and($rules['id'])->toContain('numeric')
        ->and($rules['name'])->toContain('string');
});

it('validates using spatie data pipeline', function (): void {
    $result = TestUserDataContract::validate([
        'id' => 1,
        'name' => 'John',
    ]);

    expect($result->passed())->toBeTrue();

    $failed = TestUserDataContract::validate([
        'id' => 'not an int',
    ]);

    expect($failed->failed())->toBeTrue()
        ->and($failed->errors())->toHaveKey('id')
        ->and($failed->errors())->toHaveKey('name');
});
