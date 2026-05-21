<?php

declare(strict_types=1);

return [
    'preset' => 'laravel',
    'rules' => [
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_space' => false,
    ],
    'exclude' => [
        'vendor',
        'node_modules',
    ],
];
