<?php

declare(strict_types=1);

use Satheez\MessageContracts\JsonSchema\LaravelRuleMapper;

it('maps basic string rules', function (): void {
    $mapper = new LaravelRuleMapper;

    $result = $mapper->map([
        'name' => ['required', 'string', 'min:3', 'max:255'],
    ]);

    $schema = $result['schema'];

    expect($schema['type'])->toBe('object')
        ->and($schema['required'])->toEqual(['name'])
        ->and($schema['properties']['name'])->toBeArray()
        ->and($schema['properties']['name']['type'])->toBe('string')
        ->and($schema['properties']['name']['minLength'])->toBe(3)
        ->and($schema['properties']['name']['maxLength'])->toBe(255);
});

it('maps nullable and formats', function (): void {
    $mapper = new LaravelRuleMapper;

    $result = $mapper->map([
        'email' => ['nullable', 'email'],
    ]);

    $schema = $result['schema'];

    expect($schema['required'] ?? [])->not->toContain('email')
        ->and($schema['properties']['email']['type'])->toEqual(['string', 'null'])
        ->and($schema['properties']['email']['format'])->toBe('email');
});

it('maps nested objects', function (): void {
    $mapper = new LaravelRuleMapper;

    $result = $mapper->map([
        'recipient' => ['required', 'array'],
        'recipient.email' => ['required', 'email'],
    ]);

    $schema = $result['schema'];

    expect($schema['required'])->toContain('recipient')
        ->and($schema['properties']['recipient']['type'])->toBe('object')
        ->and($schema['properties']['recipient']['required'])->toContain('email')
        ->and($schema['properties']['recipient']['properties']['email']['type'])->toBe('string');
});

it('maps array items', function (): void {
    $mapper = new LaravelRuleMapper;

    $result = $mapper->map([
        'items' => ['required', 'array', 'min:1'],
        'items.*.sku' => ['required', 'string'],
        'items.*.quantity' => ['required', 'integer', 'min:1'],
    ]);

    $schema = $result['schema'];

    expect($schema['properties']['items']['type'])->toBe('array')
        ->and($schema['properties']['items']['minItems'])->toBe(1)
        ->and($schema['properties']['items']['items']['type'])->toBe('object')
        ->and($schema['properties']['items']['items']['required'])->toEqual(['sku', 'quantity'])
        ->and($schema['properties']['items']['items']['properties']['quantity']['type'])->toBe('integer')
        ->and($schema['properties']['items']['items']['properties']['quantity']['minimum'])->toBe(1.0);
});

it('adds warnings for unsupported rules', function (): void {
    $mapper = new LaravelRuleMapper;

    $result = $mapper->map([
        'user_id' => ['required', 'integer', 'exists:users,id'],
    ]);

    expect($result['warnings'])->toHaveCount(1)
        ->and($result['warnings'][0]['field'])->toBe('user_id')
        ->and($result['warnings'][0]['message'])->toContain('Unsupported rule: exists');
});
