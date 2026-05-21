<?php

declare(strict_types=1);

use Satheez\MessageContracts\Compatibility\SchemaComparator;

it('detects no changes when schemas are identical', function (): void {
    $comparator = new SchemaComparator;
    $snapshot = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ],
    ];

    $report = $comparator->compare($snapshot, $snapshot);

    expect($report->hasBreakingChanges())->toBeFalse()
        ->and($report->hasWarnings())->toBeFalse();
});

it('detects a removed required field as breaking', function (): void {
    $comparator = new SchemaComparator;
    $prev = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id', 'email'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'email' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    $curr = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ],
    ];

    $report = $comparator->compare($prev, $curr);

    expect($report->hasBreakingChanges())->toBeTrue();
    $changes = $report->getChanges();
    expect($changes[0]['message'])->toContain('Removed required field');
});

it('detects adding a required field as breaking', function (): void {
    $comparator = new SchemaComparator;
    $prev = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ],
    ];

    $curr = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id', 'name'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    $report = $comparator->compare($prev, $curr);

    expect($report->hasBreakingChanges())->toBeTrue();
    $changes = $report->getChanges();
    expect($changes[0]['message'])->toContain('Added new required field');
});

it('detects adding an optional field as safe', function (): void {
    $comparator = new SchemaComparator;
    $prev = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ],
    ];

    $curr = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'note' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    $report = $comparator->compare($prev, $curr);

    expect($report->hasBreakingChanges())->toBeFalse()
        ->and($report->hasWarnings())->toBeFalse();

    $changes = $report->getChanges();
    expect($changes[0]['severity'])->toBe('safe')
        ->and($changes[0]['message'])->toContain('Added optional field');
});

it('detects type narrowing as breaking', function (): void {
    $comparator = new SchemaComparator;
    $prev = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => ['string', 'null']],
                    ],
                ],
            ],
        ],
    ];

    $curr = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'], // null removed
                    ],
                ],
            ],
        ],
    ];

    $report = $comparator->compare($prev, $curr);

    expect($report->hasBreakingChanges())->toBeTrue();
    $changes = $report->getChanges();
    expect($changes[0]['message'])->toContain('Field type narrowed');
});

it('detects type expansion as safe', function (): void {
    $comparator = new SchemaComparator;
    $prev = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    $curr = [
        'contracts' => [
            [
                'contract' => 'test',
                'version' => 1,
                'deprecated' => false,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => ['string', 'null']], // null added
                    ],
                ],
            ],
        ],
    ];

    $report = $comparator->compare($prev, $curr);

    expect($report->hasBreakingChanges())->toBeFalse();
    // Wait, adding nullable can be breaking for consumers, but we decided it's safe (expanded types).
});
